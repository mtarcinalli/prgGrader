<?php require_once 'header.php';



class Form {
	
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

		$cmd = "SELECT codaluno, email FROM aluno WHERE email = :email";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':email', $_REQUEST['email'], SQLITE3_TEXT);		
		$tbl = $stmt->execute();
		$row = $tbl->fetchArray(SQLITE3_ASSOC);
				
		if (! $row['codaluno']) {
			echo "inserir aluno";
			$cmd = "INSERT INTO aluno " .
				"(nome, email, senha) " .
				"VALUES " .
				"(:nome, :email, :senha) ";
			$stmt = $db->prepare($cmd);

			$stmt->bindValue(':nome', $_REQUEST['nome'], SQLITE3_TEXT);
			$stmt->bindValue(':email', $_REQUEST['email'], SQLITE3_TEXT);
			$stmt->bindValue(':senha', md5($_REQUEST['senha']), SQLITE3_TEXT);
			$ok = $stmt->execute();
			
			$cmd = "SELECT codaluno, email FROM aluno WHERE email = :email";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':email', $_REQUEST['email'], SQLITE3_TEXT);		
			$tbl = $stmt->execute();
			$row = $tbl->fetchArray(SQLITE3_ASSOC);
		}
		
		$cmd = "INSERT INTO turmaaluno " .
			"(codturma, codaluno) " .
			"VALUES " .
			"(:codturma, :codaluno) ";
		$stmt = $db->prepare($cmd);

		$stmt->bindValue(':codturma', $_REQUEST['codturma'], SQLITE3_INTEGER);
		$stmt->bindValue(':codaluno', $row['codaluno'], SQLITE3_INTEGER);
		$ok = @$stmt->execute();


		#echo "dbc: " . $stmt->rowCount();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro alterado com sucesso! [$acao]</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao alterar registro!  [$acao]</div>";
		}
		
		
	}
	
	function excluir() {
		$db = $this->db;
		$cmd = "DELETE FROM turmaaluno where codturmaaluno = :codturmaaluno";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codturmaaluno', $_REQUEST['cod'], SQLITE3_INTEGER);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}
	
	
	function importarAlunos() {

		$uploaddir = "../uploads/";
		$uploadfile = $uploaddir . "alunos.csv";

		if(is_file($uploadfile)) {
				unlink($uploadfile);
		}
		
		if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadfile)) {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao enviar arquivo!</div>";
			return;
		}	
		
		#$cmd = "ls -la ../uploads";
		#$output = shell_exec($cmd);
		#echo "<pre>$output</pre>";


		$delimitador = ',';
		$cerca = '"';

		$f = fopen($uploadfile, 'r');
		if ($f) { 
			$cabecalho = fgetcsv($f, 0, $delimitador, $cerca);
			while (!feof($f)) { 
				$row = fgetcsv($f, 0, $delimitador, $cerca);
				echo "<pre>$row[0]\t$row[1]</pre>";
				if (!$row) {
					continue;
				}
				$_REQUEST['nome'] = $row[0];
				$_REQUEST['email'] = $row[1];
				$this->salvar();
			}
		}
	}
	
	
	function formulario() {
		$db = $this->db;
		
		$cmd = "SELECT t.*, c.descricao AS curso FROM turma t INNER JOIN curso c ON c.codcurso = t.codcurso WHERE codturma = :codturma";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codturma', $_REQUEST['codturma'], SQLITE3_INTEGER);		
		$tbl = $stmt->execute();
		$rowTbl = $tbl->fetchArray();

		#print_r($rowTbl);
		
		echo "<table class='table'>" .
				"<tr>" .
				"<th>Curso:</th>" .
				"<td>$rowTbl[curso]</td>" .
				"<th>Turma:</th>" .
				"<td>$rowTbl[descricao]</td>" .
				"<th>Sigla:</th>" .
				"<td>$rowTbl[sigla]</td>" .
				"<td><a href='cadturma.php'>Voltar</a></td>" .
				"</tr>" .
				"</table>";
		?>
		<hr>
		<form action="cadturmaaluno.php" method="post" role="form" class="form-inline">
			<div class="form-group">
			<!--<label for="nome">Nome:</label>-->
			<input type="text" name="nome" id="nome" class="form-control" placeholder="Nome">
			<!--<label for="nome">E-mail:</label>-->
			<input type="email" name="email" id="email" class="form-control" placeholder="E-mail">
			<!--<label for="senha">Senha:</label>-->
			<input type="password" name="senha" id="senha" class="form-control" pattern=".{5,}" placeholder="Senha">

			<input type="hidden" name="codturma" value="<?php echo $_REQUEST['codturma']; ?>">
			<input type="hidden" name="modo" value="salvar">
		
			<button type="submit" class="btn btn-primary">Salvar</button>
			</div>
		</form>
		
		<hr>
		
		<form action="cadturmaaluno.php" method="post" enctype="multipart/form-data" class="form-inline">

			<label for="arquivo">Selecione o arquivo a ser enviado:
			<input type="file" name="arquivo" id="arquivo" accept="*.csv" class="form-control">

			<input type="hidden" name="codturma" value="<?php echo $_REQUEST['codturma']; ?>">
			
			<input type="password" name="senha" id="senha" class="form-control" pattern=".{5,}" placeholder="Senha">
			
			<input type="hidden" name="modo" value="upload">

			<button type="submit" class="btn btn-primary">Importar</button>
		</form>
		<hr>
		
		<?php
		
		
	}
	
	
	function listar() {
		$db = $this->db;
		$cmd = "SELECT " .
				"ta.*, " .
				"a.nome, a.email " .
				"FROM turmaaluno ta " .
				"INNER JOIN aluno a ON ta.codaluno = a.codaluno " .
				"WHERE codturma = :codturma " .
				"ORDER BY nome asc";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codturma', $_REQUEST['codturma'], SQLITE3_INTEGER);		
		$tbl = $stmt->execute();

		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th>Nome</th>" .
				"<th>E-mail</th>" .
				"</tr>";

		while ($row = $tbl->fetchArray()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codturmaaluno]&amp;codturma=$_REQUEST[codturma]'\">del</a> </td>";
			echo "<td>$row[nome]</td>";
			echo "<td>$row[email]</td>";
			echo "</tr>";
		}
		echo "</table>";

	}	
	
	
	function formulario2() {
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

		if ($this->modo == "upload") {
			$this->importarAlunos();
		}


		$this->formulario();
	
	
		$this->listar();
	}
}





#error_reporting(E_ALL);

$frm = new Form($arquivo);




?>

</div>
</body>
</html>