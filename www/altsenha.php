<?php 
require_once 'header.php';

#$db = new SQLite3('../db/pgrader.db');
if (! $db)
	echo "não abriu bd";


$cmd = "select " .
	"nome, email " .
	"from " .
	"aluno a " .
	"WHERE codaluno = :codaluno";
$tblAluno = $db->prepare($cmd);
$tblAluno->bindValue(':codaluno', $codaluno, PDO::PARAM_INT);
$tblAluno->execute();
$rowAluno = $tblAluno->fetch();

if ($_REQUEST['senha'] && $_REQUEST['senha2']) {
	if ($_REQUEST['senha'] != $_REQUEST['senha2']) {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Redigite a senha corretamente!</div>";
	} elseif (strlen($_REQUEST['senha']) < 5) {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Senha deve ter mais que 5 caracteres!</div>";
	} else {
		$cmd = "UPDATE aluno SET " .
			"senha = :senha " .
			"WHERE codaluno = :codaluno";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codaluno', $codaluno, PDO::PARAM_INT);
		$stmt->bindValue(':senha', md5($_REQUEST['senha']), PDO::PARAM_STR);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Senha alterada com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao alterar senha!</div>";
		}
		

		
	}
}


?>


<form method="post">
  <div class="form-group">
    <label for="usuario">Nome:</label>
	<br>
    <?php echo $rowAluno['nome']; ?>
  </div>
  <div class="form-group">
    <label for="usuario">Usuário:</label>
	<br>
    <?php echo $rowAluno['email']; ?>
  </div>
  <div class="form-group">
    <label for="senha">Senha:</label>
    <input type="password" class="form-control" id="senha" name="senha">
  </div>
  <div class="form-group">
    <label for="senha">Redigite a senha:</label>
    <input type="password" class="form-control" id="senha2" name="senha2">
  </div>
  <button type="submit" class="btn btn-primary">Salvar</button>
</form>


</div>
</body>
</html>
