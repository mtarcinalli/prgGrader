<?php
session_start();
$_SESSION['codusuario'] = 0;
$_SESSION['codtipousuario'] = 0;
$_SESSION['nome'] = 0;
$codtipousuario = 0;
$codusuario = 0;

if (@$_REQUEST['usuario'] && @$_REQUEST['senha']) {
	require_once '../src/conectdb.php';
	if (! $db) {
		echo "não abriu bd";
	}
	$cmd = "SELECT codusuario, codtipousuario, nome, alterasenha " .
			"FROM usuario " .
			"WHERE email = :email " .
			"AND senha = :senha";
	$tblLogin = $db->prepare($cmd);
	$senha = md5($_REQUEST['senha']);
	$tblLogin->bindParam(':email', $_REQUEST['usuario']);
	$tblLogin->bindParam(':senha', $senha);
	$tblLogin->execute();
	if ($row = $tblLogin->fetch()) {
		$_SESSION['codusuario'] = $row['codusuario'];
		$_SESSION['codtipousuario'] = $row['codtipousuario'];
		$_SESSION['nome'] = $row['nome'];
		$site = $_SERVER['HTTP_REFERER'];
		if (str_contains($site, "index.php")) {
				$site = str_replace("index.php", "", $site);
		}
		if ($site[-1] != '/') {
				$site .= '/';
		}
		$_SESSION['site'] = $site;
		if ($row["alterasenha"]) {
				header("Location: $_SESSION[site]altsenha.php");
		} else {
				header("Location: $_SESSION[site]tarefa.php");
		}
	} else {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Usuário ou senha inválidos!</div>";
	}
} elseif (@$_REQUEST['usuario']) {
	echo "<div class=\"alert alert-danger\" role=\"alert\">Digite uma senha!</div>";
} elseif (@$_REQUEST['senha']) {
	echo "<div class=\"alert alert-danger\" role=\"alert\">Digite um usuário!</div>";
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
</nav>
<div class="container">
<form method="post">
  <div class="form-group">
    <label for="usuario">Usuário (e-mail):</label>
    <input type="text" class="form-control" id="usuario" name="usuario">
  </div>
  <div class="form-group">
    <label for="senha">Senha:</label>
    <input type="password" class="form-control" id="senha" name="senha">
  </div>
  <button type="submit" class="btn btn-primary">Enviar</button>
</form>
</div>
</body>
</html>