<?php
//Function to check that generation is finished based on the various parameters
function checkfinished($tocreate,$types,$split,$counts,$recycle)
  {
  $finished = false;

  if ($split == "Total")
    {
    //Check if the total equals the number to create
    $totalgenerated = $counts['Missense']+$counts['Synonymous'];
    if ($totalgenerated == $tocreate)
      $finished == true;
    }
  elseif ($split == "Each")
    {
    //Check if the number of synonymous and missense variants equals the number to create
    if (($tocreate <= $counts['Missense']) AND (in_array("Missense",$types) == true) AND ($tocreate <= $counts['Synonymous']) AND (in_array("Synonymous",$types) == true))
      $finished = true;
    }

  //Finish always if recycle count reaches 50
  if ($recycle >= 50)
    $finished = true;

  Return $finished;
  }

//Function to check whether to add
function checktoadd($tocreate,$types,$counts,$split,$varianttype)
  {
  //Default is that variant is added
  $add = true;

  if ((($split == "Each") AND ($tocreate <= $counts[$varianttype])) OR ((in_array($varianttype,$types) == false)))
    $add = false;

  Return $add;
  }

//Include the DNA functions file
include_once '../functions/dna-functions.php';

//Testing flag set to true, will change to false if any non default options are added
$testing = true;

//Default to creating 200 variants, using the genes in the installed gene list, and only generating missense variants
if (isset($tocreate) == false)
  $tocreate = 6;
else
  $testing = false;

//Default gene list to test
if (isset($genes) == false)
  $genes = array("ADAMTS10","ADAMTS17","ADAMTSL2","ADAMTSL4","AR","ARX","ATRX","BRAF","BRCA1","BRCA2","CBL","CBS","CBX2","COL1A1","COL1A2","COL3A1","COL5A1","COL5A2","CYP11A1","CYP11B1","CYP17A1","CYP19A1","CYP21A2","DHCR7","DHH","DMRT1","FBN1","FBN2","HRAS","HSD17B2","HSD17B3","HSD3B2","KRAS","LHCGR","MAMLD1","MAP2K1","MAP2K2","MAP3K1","MLH1","MSH2","MYH11","NF1","NR0B1","NR5A1","NRAS","POR","PTEN","PTPN11","RAD51C","RAD51D","RAF1","RASA1","RSPO1","SHOC2","SLC2A10","SMAD3","SOS1","SOX9","SPRED1","SRY","STAR","TGFB2","TGFBR1","TGFBR2","WNT4");
else
  $testing = false;

//Choose the type of variants to generate, default is Missense and Synonymous
if (isset($types) == false)
  $types = array("Missense","Synonymous");
else
  $testing = false;

//Choose what the total number of variants generated means, each means that number will be generated for both
if (isset($split) == false)
  $split = "Each";
else
  $testing = false;

//Array to store gene details so they don't have to be looked up from APIs each time
$savedgenedetails = array();

//Initiate counts for the loop
$counts = array("Missense"=>0,"Synonymous"=>0,"Nonsense"=>0);
$recycle = 0;

//Generate the variants
$variants = array();
while (checkfinished($tocreate,$types,$split,$counts,$recycle) == false)
  {
  echo "Looped<br>";

  //Randomly pick a gene
  shuffle($genes);
  $genesymbol = $genes[0];

  //Add details about gene to array if already found, otherwise look them up
  if (isset($savedgenedetails[$genesymbol]) == true)
    {
    $genedetails = $savedgenedetails[$genesymbol];
    }
  else
    {
    //Get details about the gene to store for future cycles
    $genedetails = array();

    include 'find-ids.php';
    include 'get-exons.php';

    //Make the exon ranges exclude the first and last codons
    $exons[0]['Start'] = 3;
    $last = count($exons)-1;
    $exons[$last]['End'] = $exons[$last]['End']-2;

    $genedetails['Exons'] = $exons;

    //Get the gene sequence from the CDS identified by the ENST ID
    $genedetails['Sequence'] = getrawdatafromapi($ids['ENST'],"EnsemblSequenceFromENST");

    $savedgenedetails[$genesymbol] = $genedetails;
    }

  //Get the exons and choose position that isn't at an exon boundary or N or C terminal residue
  $exons = $savedgenedetails[$genesymbol]['Exons'];
  shuffle($exons);
  $exon = $exons[0];
  $rangestart = $exon['Start'];
  $rangeend = $exon['End'];
  $position = mt_rand($rangestart,$rangeend);

  //Get the nucleotide where the variant is and generate the variant
  $substrposition = $position-1;
  $nucleotide = substr($genedetails['Sequence'],$substrposition,1);
  $options = array("A","C","G","T");
  foreach ($options as $optionskey=>$option)
    {
    //Remove from options if this is the original nucleotide
    if ($option == $nucleotide)
      unset($options[$optionskey]);
    }
  shuffle($options);
  $newvariant = $options[0];

  //Identify whether a variant is a missense or a synonymous variant

  //Create codon template
  $codonposition = $position%3;
  if ($codonposition == 1)
    $codontemplate = "*" . substr($genedetails['Sequence'],$substrposition+1,2);
  elseif ($codonposition == 2)
    $codontemplate = substr($genedetails['Sequence'],$substrposition-1,1) . "*" . substr($genedetails['Sequence'],$substrposition+1,1);
  elseif ($codonposition == 0)
    $codontemplate = substr($genedetails['Sequence'],$substrposition-2,2) . "*";

  //Make codons and amino acids
  $originalcodon = str_replace("*",$nucleotide,$codontemplate);
  $newcodon = str_replace("*",$newvariant,$codontemplate);
  $originalaminoacid = translatecodon($originalcodon);
  $newaminoacid = translatecodon($newcodon);

  //Define type of variant
  if ($newaminoacid == $originalaminoacid)
    $varianttype = "Synonymous";
  elseif ($newaminoacid == "*")
    $varianttype = "Nonsense";
  else
    $varianttype = "Missense";

  //Format variant description
  $variantdescription = $genesymbol . " c." . $position . " " . $nucleotide . "&gt;" . $newvariant . "(p." . $originalaminoacid . ">" . $newaminoacid . ")";

  //Add to the list of variants already generated
  if ((in_array($variantdescription,$variants) == false) AND (checktoadd($tocreate,$types,$counts,$split,$varianttype) == true))
    {
    $variantline = array("Gene"=>$genesymbol,"Position"=>$position,"VariantDescription"=>$variantdescription,"Type"=>$varianttype);
    array_push($variants,$variantline);

    //Keep track of the number of variants
    $counts[$varianttype] = $counts[$varianttype]+1;
    }
  else
    $recycle = $recycle+1;
  }

if ($testing == true)
  {
  foreach ($variants as $variantkey=>$variant)
    {
    $variants[$variantkey] = $variant['VariantDescription'] . " = " . $variant['Type'];
    }

  $variantsdisplay = "<p>" . implode("<br>",$variants) . "</p>";
  echo $variantsdisplay;
  }
?>
