<?php
//Testing flag set to true, will change to false if any non default options are added
$testing = true;

//Default to creating 200 variants, using the genes in the installed gene list, and only generating missense variants
if (isset($tocreate) == false)
  $tocreate = 200;
else
  $testing = false;

if (isset($genes) == false)
  $genes = array("ADAMTS10","ADAMTS17","ADAMTSL2","ADAMTSL4","AR","ARX","ATRX","BRAF","BRCA1","BRCA2","CBL","CBS","CBX2","COL1A1","COL1A2","COL3A1","COL5A1","COL5A2","CYP11A1","CYP11B1","CYP17A1","CYP19A1","CYP21A2","DHCR7","DHH","DMRT1","FBN1","FBN2","HRAS","HSD17B2","HSD17B3","HSD3B2","KRAS","LHCGR","MAMLD1","MAP2K1","MAP2K2","MAP3K1","MLH1","MSH2","MSH6","MYH11","NF1","NR0B1","NR5A1","NRAS","POR","PTEN","PTPN11","RAD51C","RAD51D","RAF1","RASA1","RSPO1","SHOC2","SLC2A10","SMAD3","SOS1","SOX9","SPRED1","SRD5A2","SRY","STAR","TGFB2","TGFBR1","TGFBR2","WNT4","WT1");
else
  $testing = false;

if (isset($types) == false)
  $types = array("Missense");
else
  $testing = false;

//Array to store gene details so they don't have to be looked up from APIs each time
$savedgenedetails = array();

//Generate the variants
$variants = array();
while (count($variants) < $tocreate)
  {
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

  //Format variant description
  $variantdescription = $genesymbol . " c." . $position . " " . $nucleotide . "&gt;" . $newvariant;

  //Keep a count of the variants that have been generated
  if (in_array($variantdescription,$variants) == false)
    {
    //Initiate paragraph for first variant
    if (count($variants) == 0)
      echo "<p>";
    else
      echo "<br>";

    //Add to the list of variants already generated
    array_push($variants,$genesymbol);
    echo $variantdescription;
    }
  }

//Close the paragraph where the variants are listed
echo "</p>";

//Clear the buffer
unset($variants);
?>
