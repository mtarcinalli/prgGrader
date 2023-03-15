<?php
require_once 'conectdb.php';

function autoloader($class) {
	$class = strtolower($class);
	if ($class == "form0" || $class == "obj2db") {
		include("../src/modules/obj2db/src/" . $class . ".php");
		return;
	}
	include("../src/classes/" . $class . ".php");
}
spl_autoload_register("autoloader");


# Nome do Arquivo
$arquivo = substr(strrchr($_SERVER['SCRIPT_FILENAME'], "/"), 1 );

session_start();
$codusuario = (isset($_SESSION['codusuario']) ? $_SESSION['codusuario'] : false);
$codtipousuario = (isset($_SESSION['codtipousuario']) ? (int)$_SESSION['codtipousuario'] : false);
$nome = (isset($_SESSION['nome']) ? $_SESSION['nome'] : false);

if ($arquivo != "index.php" and ! $codusuario) {
	header('Location: index.php');
}

$showHeader = true;
if (! isset($_REQUEST['modo'])) {
	$showHeader = true;
} elseif (substr($_REQUEST['modo'], 0, 8) == "download") {
	$showHeader = false;
}

if ($showHeader) {
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>prgGrader</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
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
		h2{
			font-size: 1rem;
		}
		h4{
			font-size: 1rem;
		}
		@media print {
			.noPrint {
				display:none;
			}
		}
	</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
	<a class="navbar-brand" href="index.php">prgGrader</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#myNavbar" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>

	<?php if ($codtipousuario) { ?>
	<div class="collapse navbar-collapse" id="myNavbar">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item"><a class="nav-link" href="tarefa.php">Tarefas</a></li>
			<?php if ($codtipousuario < 4) { ?>
				<li class="nav-item"><a class="nav-link" href="cadcurso.php">Cursos</a></li>
				<li class="nav-item"><a class="nav-link" href="cadturma.php">Turmas</a></li>
				<li class="nav-item"><a class="nav-link" href="cadaluno.php">Usuários</a></li>
				<li class="nav-item"><a class="nav-link" href="cadplugin.php">Corretores</a></li>
				<li class="nav-item"><a class="nav-link" href="cadtarefa.php">Tarefas</a></li>
				<li class="nav-item"><a class="nav-link" href="relnotas.php">Notas</a></li>
			<?php } ?>
		</ul>
		<ul class="navbar-nav navbar-right">
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Usuário
				</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					<a class="dropdown-item" href="#"><?php echo$nome; ?></a>
					<a class="dropdown-item" href="altsenha.php">Alterar Senha</a>
			</li>
			<li class="nav-item"><a class="nav-link" href="index.php"><span class="glyphicon glyphicon-log-in"></span> Sair</a></li>
		</ul>
	</div>
	<?php } ?>
</nav>
<div class="container">
<?php
}