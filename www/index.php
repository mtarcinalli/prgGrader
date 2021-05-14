<?php require_once 'header.php';

session_start();
$_SESSION['codaluno'] = 0; 
$_SESSION['codtipousuario'] = 0; 
$_SESSION['nome'] = 0; 

$codtipousuario = 0;
$codaluno = 0;


if (@$_REQUEST['usuario'] && @$_REQUEST['senha']) {

	$db = new SQLite3('../db/pgrader.db');
	if (! $db)
		echo "não abriu bd";
	
	$cmd = "SELECT codaluno, codtipousuario, nome " .
			"FROM aluno " .
			"WHERE email = :email " .
			"AND senha = :senha";
	
	$stmt = $db->prepare($cmd);
	$stmt->bindValue(':email', $_REQUEST['usuario'], SQLITE3_TEXT);
	$stmt->bindValue(':senha', md5($_REQUEST['senha']), SQLITE3_TEXT);
	$tblLogin = $stmt->execute();
	
	if ($row = $tblLogin->fetchArray(SQLITE3_ASSOC)) {
		$_SESSION['codaluno'] = $row['codaluno'];
		$_SESSION['codtipousuario'] = $row['codtipousuario'];
		$_SESSION['nome'] = $row['nome'];
		if (md5($_REQUEST['senha']) == 'b9196f70ad74e02f8faaf4a21755d377')
			header('Location: altsenha.php');
		else
			header('Location: tarefa.php');
	} else {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Usuário ou senha inválidos!</div>";
	} 


	
} elseif (@$_REQUEST['usuario']) {
	echo "<div class=\"alert alert-danger\" role=\"alert\">Digite uma senha!</div>";
} elseif (@$_REQUEST['senha']) {
	echo "<div class=\"alert alert-danger\" role=\"alert\">Digite um usuário!</div>";
}


?>


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