<?php require_once 'header.php';


class Formulario {
	
	private $db;
	private $modo;
	private $arquivo;
	
	
	function __construct($arquivo, $db) {
		$this->modo = $_REQUEST["modo"];
		$this->arquivo = $arquivo;
		$this->db = $db;
		$this->acao();
	}
	
	function salvar() {
		$db = $this->db;
		if ($_REQUEST['cp'] != "") {
			$cmd = "UPDATE tarefa SET " .
					"descricao = :descricao, " .
					"sigla = :sigla, " .
					"instrucoes = :instrucoes, " .
					"observacao = :observacao " .
					"WHERE codtarefa = :cp";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':cp', $_REQUEST['cp'], PDO::PARAM_INT);
			$acao = "alterar";
			$_REQUEST['cod'] = "";
		} else {
			$cmd = "INSERT INTO tarefa " .
				"(descricao, sigla, instrucoes, observacao) " .
				"VALUES " .
				"(:descricao, :sigla, :instrucoes, :observacao) ";
			$stmt = $db->prepare($cmd);
			$acao = "incluir";
		}
		$stmt->bindValue(':descricao', $_REQUEST['descricao'], PDO::PARAM_STR);
		$stmt->bindValue(':sigla', $_REQUEST['sigla'], PDO::PARAM_STR);
		$stmt->bindValue(':instrucoes', $_REQUEST['instrucoes'], PDO::PARAM_STR);
		$stmt->bindValue(':observacao', $_REQUEST['observacao'], PDO::PARAM_STR);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro alterado com sucesso! [$acao]</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao alterar registro!  [$acao]</div>";
		}
	}
	
	function excluir() {
		$db = $this->db;
		$cmd = "DELETE FROM tarefa where codtarefa = :codtarefa";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefa', $_REQUEST['cod'], PDO::PARAM_INT);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}
		
	
	function listar() {
		$db = $this->db;
		$cmd = "SELECT " .
				"t.* " .
				"FROM tarefa t " .
				"ORDER BY sigla desc";
		$tbl = $db->prepare($cmd);
		$tbl->execute();

		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th></th>" .
				"<th></th>" .
				"<th>Tarefa</th>" .
				"<th>Sigla</th>" .
				"<th>Instruções</th>" .
				"<th>Observações</th>" .
				"</tr>";

		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-pencil\"></span></a> </td>";
			echo "<td><a href='cadtarefaturma.php?codtarefa=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-send\"></a> </td>";
			echo "<td>$row[descricao]</td>";
			echo "<td>$row[sigla]</td>";
			echo "<td>" . nl2br($row['instrucoes']) . "</td>";
			echo "<td>" . nl2br($row['observacao']) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}	
	
	
	function formulario() {
		$db = $this->db;
		if ($this->modo =="alterar") {
			$cmd = "SELECT * FROM tarefa WHERE codtarefa = :codtarefa";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codtarefa', $_REQUEST['cod']);
			$tbl->execute();
			$rowTbl = $tbl->fetch();
		}
		?>
		<form action="<?php echo $this->arquivo; ?>" method="post" role="form">
			<div class="form-group">
				<label for="descricao">Tarefa:</label>
				<input type="text" name="descricao" id="descricao" value="<?php echo $rowTbl["descricao"]; ?>" class="form-control">
				<label for="sigla">Sigla:</label>
				<input type="text" name="sigla" id="sigla" value="<?php echo $rowTbl["sigla"]; ?>" class="form-control">
				
				<label for="instrucoes">Instruções:</label>
				<textarea name="instrucoes" id="instrucoes" class="form-control"><?php echo $rowTbl["instrucoes"]; ?></textarea>
				
				<label for="observacao">Observações:</label>
				<textarea name="observacao" id="observacao" class="form-control"><?php echo $rowTbl["observacao"]; ?></textarea>

				<input type="hidden" name="cp" value="<?php echo $_REQUEST['cod']; ?>">
				<input type="hidden" name="modo" value="salvar">
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary">Salvar</button>
			</div>
		</form>
		<br>
		<?php
		
		
	}
	
	
	
	function acao() {
		if ($this->modo == "salvar") {
			$this->salvar();
		}

		if ($this->modo == "exclui") {
			$this->excluir();
		}


		$this->formulario();	
	
		$this->listar();
	}
}





#error_reporting(E_ALL);

$frm = new Formulario($arquivo, $db);




?>

</div>
</body>
</html>
