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
$genedetails = array();

//Generate the variants
$variants = array();
while (count($variants) < $tocreate)
  {
  //Randomly pick a gene
  shuffle($genes);
  $gene = $genes[0];

  array_push($variants,$gene);
  }

//Echo variants as array if testing is true
if ($testing == true)
  print_r($variants);
?>
