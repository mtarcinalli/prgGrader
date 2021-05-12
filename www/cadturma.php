<?php require_once 'header.php';



class Formulario {
	
	private $db;
	private $modo;
	private $arquivo;
	
	
	function __construct($arquivo) {
		$this->modo = $_REQUEST["modo"];
		$this->arquivo = $arquivo;
		$this->db = new SQLite3('../db/pgrader.db');
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
			$stmt->bindValue(':cp', $_REQUEST['cp'], SQLITE3_INTEGER);
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
		$stmt->bindValue(':codcurso', $_REQUEST['codcurso'], SQLITE3_INTEGER);
		$stmt->bindValue(':descricao', $_REQUEST['descricao'], SQLITE3_TEXT);
		$stmt->bindValue(':sigla', $_REQUEST['sigla'], SQLITE3_TEXT);
		$stmt->bindValue(':observacao', $_REQUEST['observacao'], SQLITE3_TEXT);
		$ok = $stmt->execute();
		#echo "dbc: " . $stmt->rowCount();
		if ($ok && $db->changes()) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro alterado com sucesso! [$acao]</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao alterar registro!  [$acao]</div>";
		}
		
		
	}
	
	function excluir() {
		$db = $this->db;
		$cmd = "DELETE FROM turma where codturma = :codturma";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codturma', $_REQUEST['cod'], SQLITE3_INTEGER);
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
				"t.*, " .
				"c.descricao as curso " .
				"FROM turma t " .
				"INNER JOIN curso c ON t.codcurso = c.codcurso " .
				"ORDER BY sigla desc";
		$tbl = $db->query($cmd);

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

		while ($row = $tbl->fetchArray()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codturma]'\">del</a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codturma]'\">edt</a> </td>";
			echo "<td><a href='cadturmaaluno.php?modo=alunos&amp;codturma=$row[codturma]'\">alunos</a> </td>";

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
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':codturma', $_REQUEST['cod'], SQLITE3_INTEGER);
			
			$tbl = $stmt->execute();
			$rowTbl = $tbl->fetchArray();
		}
		?>
		<form action="<?php echo $this->arquivo; ?>" method="post" role="form">
			<div class="form-group">
				<label for="descricao">Turma:</label>
				<input type="text" name="descricao" id="descricao" value="<?php echo $rowTbl["descricao"]; ?>" class="form-control">
				<label for="sigla">Sigla:</label>
				<input type="text" name="sigla" id="sigla" value="<?php echo $rowTbl["sigla"]; ?>" class="form-control">
				
				<label for="codcurso">Curso:</label>
				<select name="codcurso" id="codcurso" class="form-control">
					<option>[Curso]</option>
					<?php
					$cmd = "SELECT codcurso, descricao FROM curso ORDER BY descricao";
					$stmt = $db->prepare($cmd);
					$tbl = $stmt->execute();
					while ($row = $tbl->fetchArray()){
						echo "<option value='$row[codcurso]' ";
						if ($row['codcurso'] == $rowTbl['codcurso'])
							echo " selected";
						echo ">$row[descricao]</option>";
					}
					?>
				</select>
				
				<label for="observacao">Observações:</label>
				<input type="text" name="observacao" id="observacao" class="form-control" value="<?php echo $rowTbl['observacao']; ?>">
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

$frm = new Formulario($arquivo);




?>

</div>
</body>
</html>