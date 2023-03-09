<?php require_once 'header.php';

if ($codtipousuario > 3) {
	die;
}

class Formulario {	
	private $db;
	private $modo;
	private $arquivo;
	
	function __construct($arquivo, $db) {
		$this->modo = (isset($_REQUEST["modo"]) ? $_REQUEST["modo"] : "");
		$this->arquivo = $arquivo;
		$this->db = $db;
		if (! $this->db)
			echo "não abriu bd";
		$this->acao();
	}
	
	function salvar() {
		$db = $this->db;
		if ($_REQUEST['cp'] != "") {
			$cmd = "UPDATE turma SET " .
					"codcurso = :codcurso, " .
					"descricao = :descricao, " .
					"sigla = :sigla, " .
					"observacao = :observacao " .
					"WHERE codturma = :cp";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':cp', $_REQUEST['cp'], PDO::PARAM_INT);
			$acao = "alterar";
			$_REQUEST['cod'] = "";
		} else {
			$cmd = "INSERT INTO turma " .
				"(codcurso, descricao, sigla, observacao) " .
				"VALUES " .
				"(:codcurso, :descricao, :sigla, :observacao) ";
			$stmt = $db->prepare($cmd);
			$acao = "incluir";
		}
		$stmt->bindValue(':codcurso', $_REQUEST['codcurso'], PDO::PARAM_INT);
		$stmt->bindValue(':descricao', $_REQUEST['descricao'], PDO::PARAM_STR);
		$stmt->bindValue(':sigla', $_REQUEST['sigla'], PDO::PARAM_STR);
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
		$cmd = "DELETE FROM turma where codturma = :codturma";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codturma', $_REQUEST['cod'], PDO::PARAM_INT);
		try {
			$ok = $stmt->execute();
		} catch (Exception $e) {
			$ok = false;
			if ($e->getCode() == 23503) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Turma ainda possui registros relacionados!</div>";
				return;
			}
		}
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}
		
	function listar() {
		$db = $this->db;
		$cmd = "SELECT " .
				"t.*, " .
				"c.descricao as curso " .
				"FROM turma t " .
				"LEFT JOIN curso c ON t.codcurso = c.codcurso " .
				"ORDER BY sigla desc";
		$tbl = $db->prepare($cmd);
		$tbl->execute();
		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th></th>" .
				"<th></th>" .
				"<th>Curso</th>" .
				"<th>Turma</th>" .
				"<th>Sigla</th>" .
				"<th>Observações</th>" .
				"</tr>";
		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codturma]'\"><span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codturma]'\"><span class=\"glyphicon glyphicon-pencil\"></span></a> </td>";
			echo "<td><a href='cadturmaaluno.php?modo=alunos&amp;codturma=$row[codturma]'\"><span class=\"glyphicon glyphicon-education\"></span></a> </td>";
			echo "<td>$row[curso]</td>" . 
				"<td>$row[descricao]</td>" .
				"<td>$row[sigla]</td>" .
				"<td>$row[observacao]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}	
	
	function formulario() {
		$db = $this->db;
		if ($this->modo =="alterar") {
			$cmd = "SELECT * FROM turma WHERE codturma = :codturma";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codturma', $_REQUEST['cod'], PDO::PARAM_INT);
			$tbl->execute();
			$rowTbl = $tbl->fetch();
		}
		?>
		<form action="<?php echo $this->arquivo; ?>" method="post" role="form">
			<div class="form-group">
				<label for="descricao">Turma:</label>
				<input type="text" name="descricao" id="descricao" value="<?php echo (isset($rowTbl) ? $rowTbl["descricao"] : ""); ?>" class="form-control">
				<label for="sigla">Sigla:</label>
				<input type="text" name="sigla" id="sigla" value="<?php echo (isset($rowTbl) ? $rowTbl["sigla"] : ""); ?>" class="form-control">
				<label for="codcurso">Curso:</label>
				<select name="codcurso" id="codcurso" class="form-control">
					<option>[Curso]</option>
					<?php
					$cmd = "SELECT codcurso, descricao FROM curso ORDER BY descricao";
					$tbl = $db->prepare($cmd);
					$tbl->execute();
					while ($row = $tbl->fetch()){
						echo "<option value='$row[codcurso]' ";
						if (isset($rowTbl))
							if ($row['codcurso'] == $rowTbl['codcurso'])
								echo " selected";
						echo ">$row[descricao]</option>";
					}
					?>
				</select>
				<label for="observacao">Observações:</label>
				<textarea name="observacao" id="observacao" class="form-control"><?php echo (isset($rowTbl) ? $rowTbl["observacao"] : ""); ?></textarea>
				<input type="hidden" name="cp" value="<?php echo (isset($_REQUEST['cod']) ? $_REQUEST['cod'] : ""); ?>">
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