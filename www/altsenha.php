<?php 
require_once 'header.php';

if (! $db) {
	echo "não abriu bd";
}

$cmd = "select " .
	"nome, email " .
	"from " .
	"usuario " .
	"WHERE codusuario = :codusuario";
$tblUsuario = $db->prepare($cmd);
$tblUsuario->bindValue(':codusuario', $codusuario, PDO::PARAM_INT);
$tblUsuario->execute();
$rowUsuario = $tblUsuario->fetch();

if (isset($_REQUEST['senha']) && isset($_REQUEST['senha2'])) {
	if ($_REQUEST['senha'] != $_REQUEST['senha2']) {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Redigite a senha corretamente!</div>";
	} elseif (strlen($_REQUEST['senha']) < 5) {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Senha deve ter mais que 5 caracteres!</div>";
	} else {
		$cmd = "UPDATE usuario SET " .
			"senha = :senha , alterasenha = false " .
			"WHERE codusuario = :codusuario";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codusuario', $codusuario, PDO::PARAM_INT);
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
    <?php echo $rowUsuario['nome']; ?>
  </div>
  <div class="form-group">
    <label for="usuario">Usuário:</label>
	<br>
    <?php echo $rowUsuario['email']; ?>
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