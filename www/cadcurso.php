<?php require_once 'header.php'; ?>

	<form action="cadcurso.php" method="post" role="form">
		<div class="form-group">
			<label for="descricao">Curso:</label>
			<input type="text" name="descricao" id="descricao" class="form-control">
			<label for="sigla">Sigla:</label>
			<input type="text" name="sigla" id="sigla" class="form-control">
			<label for="observacao">Observações:</label>
			<input type="text" name="observacao" class="form-control">
			<input type="hidden" name="modo" value="salvar">
		</div>
		<div class="form-group">
			<button type="submit" class="btn btn-primary">Salvar</button>
		</div>
	</form>
	<br>

<?php
#error_reporting(E_ALL);

#$db = new SQLite3('../db/pgrader.db');
if (! $db)
	echo "não abriu bd";


	
if (@$_REQUEST['modo'] == "salvar") {
	$cmd = "INSERT INTO curso " .
		"(descricao, sigla, observacao) " .
		"VALUES " .
		"(:descricao, :sigla, :observacao) ";
	$stmt = $db->prepare($cmd);
	$stmt->bindValue(':descricao', $_REQUEST['descricao'], SQLITE3_TEXT);
	$stmt->bindValue(':sigla', $_REQUEST['sigla'], SQLITE3_TEXT);
	$stmt->bindValue(':observacao', $_REQUEST['observacao'], SQLITE3_TEXT);
	$ok = $stmt->execute();
	if ($ok) {
		echo "<div class=\"alert alert-success\" role=\"alert\">Registro alterado com sucesso!</div>";
	} else {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao alterar registro!</div>";
	}
}

if (@$_REQUEST['modo'] == "exclui") {
	$cmd = "DELETE FROM curso where codcurso = :codcurso";
	$stmt = $db->prepare($cmd);
	$stmt->bindValue(':codcurso', $_REQUEST['cod'], SQLITE3_INTEGER);
	$ok = $stmt->execute();
	if ($ok) {
		echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
	} else {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
	}
}



$cmd = "select * from curso order by descricao asc";
$tbl = $db->query($cmd);

echo "<table class=\"table table-striped\">" .
		"<tr>" .
		"<th></th>" .
		"<th>Curso</th>" .
		"<th>Sigla</th>" .
		"<th>Observações</th>" .
		"</tr>";

while ($row = $tbl->fetch()) {
	echo "<tr>";
	echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codcurso]'\">del</a> </td>";
	echo "<td>$row[descricao]</td>" .
		"<td>$row[sigla]</td>" .
		"<td>$row[observacao]</td>";
	echo "</tr>";
}

echo "</table>";

?>

</div>
</body>
</html>
