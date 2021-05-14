<?php 
# Nome do Arquivo
$arquivo = substr(strrchr($_SERVER['SCRIPT_FILENAME'], "/"), 1 );

session_start();
$codaluno = $_SESSION['codaluno'];
$codtipousuario = $_SESSION['codtipousuario'];
$nome = $_SESSION['nome'];

if ($arquivo != "index.php" and ! $codaluno) {
	header('Location: index.php');
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>prgGrader</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link type="text/css" rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
   		<a class="navbar-brand" href="#">prgGrader</a>
  	</div>
	<?php if ($codtipousuario) { ?>
   	<div class="collapse navbar-collapse" id="myNavbar">
    	<ul class="nav navbar-nav">
			<li><a href="tarefa.php">Tarefas</a></li>
			<?php if ($codtipousuario < 4) { ?>
				<li><a href="cadcurso.php">Cursos</a></li>
				<li><a href="cadturma.php">Turmas</a></li>
				<li><a href="cadtarefa.php">Tarefas</a></li>
			<?php } ?>      		
    	</ul>
		<ul class="nav navbar-nav navbar-right">
			<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-user"></span> Usuário <span class="caret"></span></a>
			
			
			<ul class="dropdown-menu">
			  <li><a href="#"><?php echo$nome; ?></a></li>
			  <li><a href="altsenha.php">Alterar Senha</a></li>
			</ul>			
			
			
			<li><a href="index.php"><span class="glyphicon glyphicon-log-in"></span> Sair</a></li>
			
		</ul>
	</div>
	<?php } ?>
</nav>
<div class="container">