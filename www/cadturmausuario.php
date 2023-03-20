<?php require_once 'header.php';

if ($codtipousuario > 3) {
	die;
}

class Form {
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
		$cmd = "SELECT codusuario, email FROM usuario WHERE email = :email";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':email', $_REQUEST['email'], PDO::PARAM_STR);
		$tbl->execute();
		$row = $tbl->fetch();
		if (! $row) {
			# inserindo usuario
			$cmd = "INSERT INTO usuario " .
				"(codtipousuario, nome, email, senha, alterasenha) " .
				"VALUES " .
				"(4, :nome, :email, :senha, true) ";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':nome', $_REQUEST['nome'], PDO::PARAM_STR);
			$tbl->bindValue(':email', $_REQUEST['email'], PDO::PARAM_STR);
			$tbl->bindValue(':senha', md5($_REQUEST['senha']), PDO::PARAM_STR);
			$ok = $tbl->execute();
			# recuperando codusuario
			$cmd = "SELECT codusuario, email FROM usuario WHERE email = :email";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':email', $_REQUEST['email'], PDO::PARAM_STR);
			$tbl->execute();
			$row = $tbl->fetch();
		}
		# inserindo usuario em turma
		try {
			$cmd = "INSERT INTO turmausuario " .
				"(codturma, codusuario) " .
				"VALUES " .
				"(:codturma, :codusuario) ";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
			$tbl->bindValue(':codusuario', $row['codusuario'], PDO::PARAM_INT);
			$ok = @$tbl->execute();
		} catch (Exception $e) {
			$ok = false;
			if ($e->getCode() == 23505) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Aluno já existente na turma!</div>";
				return;
			}
		}
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro alterado com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao alterar registro!</div>";
		}
	}

	function excluir() {
		$db = $this->db;
		$cmd = "DELETE FROM turmausuario where codturmausuario = :codturmausuario";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codturmausuario', $_REQUEST['cod'], PDO::PARAM_INT);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}

	function importarUsuarios() {
		$uploaddir = "../uploads/";
		$uploadfile = $uploaddir . "usuarios.csv";
		if(is_file($uploadfile)) {
				unlink($uploadfile);
		}
		if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadfile)) {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao enviar arquivo!</div>";
			return;
		}
		$delimitador = ',';
		$cerca = '"';
		$f = fopen($uploadfile, 'r');
		if ($f) {
			while (!feof($f)) {
				$row = fgetcsv($f, 0, $delimitador, $cerca);
				if (! $row || $row[0] == "nome") {
					continue;
				}
				echo "<pre>$row[0]\t$row[1]</pre>";
				$_REQUEST['nome'] = $row[0];
				$_REQUEST['email'] = $row[1];
				$_REQUEST['senha'] = $row[2];
				$this->salvar();
			}
		}
	}

	function formulario() {
		$db = $this->db;
		$cmd = "SELECT t.*, c.descricao AS curso FROM turma t INNER JOIN curso c ON c.codcurso = t.codcurso WHERE codturma = :codturma";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
		$tbl->execute();
		$rowTbl = $tbl->fetch();
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
		<form action="cadturmausuario.php" method="post" role="form" class="form-inline">
			<div class="form-group">
			<input type="text" name="nome" id="nome" class="form-control" placeholder="Nome">
			<input type="email" name="email" id="email" class="form-control" placeholder="E-mail">
			<input type="password" name="senha" id="senha" class="form-control" pattern=".{5,}" placeholder="Senha">
			<input type="hidden" name="codturma" value="<?php echo $_REQUEST['codturma']; ?>">
			<input type="hidden" name="modo" value="salvar">
			<button type="submit" class="btn btn-primary">Salvar</button>
			</div>
		</form>

		<hr>

		<form action="cadturmausuario.php" method="post" enctype="multipart/form-data" class="form-inline">
			<label for="arquivo">Arquivo usuarios*:
			<input type="file" name="arquivo" id="arquivo" accept=".csv" class="form-control">
			</label>
			<input type="hidden" name="codturma" value="<?php echo $_REQUEST['codturma']; ?>">
			<input type="hidden" name="modo" value="upload">
			<button type="submit" class="btn btn-primary">Importar</button>
		</form>
		<p>* Arquivo no formato csv sem cabeçalho e com 3 colunas separadas por , : nome, e-mail e senha.</p>
		<hr>
		<?php
	}


	function listar() {
		$db = $this->db;
		$cmd = "SELECT " .
				"ta.*, " .
				"a.nome, a.email " .
				"FROM turmausuario ta " .
				"INNER JOIN usuario a ON ta.codusuario = a.codusuario " .
				"WHERE codturma = :codturma " .
				"ORDER BY nome asc";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
		$tbl->execute();
		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th>Nome</th>" .
				"<th>E-mail</th>" .
				"</tr>";
		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codturmausuario]&amp;codturma=$_REQUEST[codturma]'\"><span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td>$row[nome]</td>";
			echo "<td>$row[email]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	function acao() {
		if ($this->modo == "salvar") {
			$this->salvar();
		}
		if ($this->modo == "exclui") {
			$this->excluir();
		}
		if ($this->modo == "upload") {
			$this->importarUsuarios();
		}
		$this->formulario();
		$this->listar();
	}
}

#error_reporting(E_ALL);
$frm = new Form($arquivo, $db);

?>
</div>
</body>
</html>