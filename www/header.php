<?php 
# Nome do Arquivo
$arquivo = substr(strrchr($_SERVER['SCRIPT_FILENAME'], "/"), 1 );
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>estudos: conteúdo</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link type="text/css" rel="stylesheet" href="css/bootstrap.min.css">
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/prototype.js"></script>
	<style>
		.option-button {
		    height:100%;
		}
		
		.media-object {
		    height: 100px;
		}

		#lista img {
			max-width: 100%;
		}
		
		@media print {
			.noPrint {
				display:none;
			}
		}
		
	</style>
</head>
<body>
<nav class="navbar navbar-default">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>                        
		</button>
   		<a class="navbar-brand" href="#">Estudos</a>
  	</div>
   	<div class="collapse navbar-collapse" id="myNavbar">
    	<ul class="nav navbar-nav">
      		<li <?= ($arquivo == "index.php" ? 'class="active"' : ""); ?>><a href="index.php">Conteúdo</a></li>
      		<li <?= ($arquivo == "cadcaderno.php" ? 'class="active"' : ""); ?>><a href="cadcaderno.php">Cadernos</a></li>
      		<li <?= ($arquivo == "caddisciplina.php" ? 'class="active"' : ""); ?>><a href="caddisciplina.php">Disciplinas</a></li>
      		<li <?= ($arquivo == "cadquestoes.php" ? 'class="active"' : ""); ?>><a href="cadquestoes.php">Questões</a></li>
      		<!-- <li><a href="#">Matérias</a></li>-->
      		<li <?= ($arquivo == "relatorio.php" ? 'class="active"' : ""); ?>><a href="relatorio.php">Relatórios</a></li>
    	</ul>
	</div>
</nav>
<div class="container">