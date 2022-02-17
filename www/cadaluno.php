<?php require_once 'header.php';

class Formulario {	
	private $db;
	private $modo;
	private $arquivo;
	
	function __construct($arquivo, $db) {
		$this->modo = $_REQUEST["modo"];
		$this->arquivo = $arquivo;
		$this->db = $db;
		if (! $this->db)
			echo "não abriu bd";
		$this->acao();
	}
	
	function salvar() {
		$db = $this->db;
		if ($_REQUEST['cp'] != "") {
			$cmd = "UPDATE aluno SET " .
					"codtipousuario = :codtipousuario, " .
					"nome = :nome, " .
					"email = :email, " .
					($_REQUEST['senha'] ? "senha = :senha, " : "") .
					"observacao = :observacao " .
					"WHERE codaluno = :cp";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':cp', $_REQUEST['cp'], PDO::PARAM_INT);
			
			$acao = "alterar";
			$_REQUEST['cod'] = "";
		} else {
			$cmd = "INSERT INTO aluno " .
				"(codtipousuario, nome, email, senha, observacao) " .
				"VALUES " .
				"(:codtipousuario, :nome, :email, :senha, :observacao) ";
			$stmt = $db->prepare($cmd);
			$acao = "incluir";
		}
		$stmt->bindValue(':codtipousuario', $_REQUEST['codtipousuario'], PDO::PARAM_INT);
		$stmt->bindValue(':nome', $_REQUEST['nome'], PDO::PARAM_STR);
		$stmt->bindValue(':email', $_REQUEST['email'], PDO::PARAM_STR);
		if ($_REQUEST['senha']) {
			$stmt->bindValue(':senha', md5($_REQUEST['senha']), PDO::PARAM_STR);
		}
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
		$cmd = "DELETE FROM aluno where codaluno = :codaluno";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codaluno', $_REQUEST['cod'], PDO::PARAM_INT);
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
				"a.*, " .
				"t.descricao as tipousuario " .
				"FROM aluno a " .
				"LEFT JOIN tipousuario t ON a.codtipousuario = t.codtipousuario " .
				"ORDER BY nome asc";
		$tbl = $db->prepare($cmd);
		$tbl->execute();
		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th></th>" .
				"<th>Nome</th>" .
				"<th>E-mail</th>" .
				"<th>Tipo</th>" .
				"<th>Observações</th>" .
				"</tr>";
		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codaluno]'\"><span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codaluno]'\"><span class=\"glyphicon glyphicon-pencil\"></span></a> </td>";
			echo "<td>$row[nome]</td>" . 
				"<td>$row[email]</td>" .
				"<td>$row[tipousuario]</td>" .
				"<td>$row[observacao]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}	
	
	function formulario() {
		$db = $this->db;
		if ($this->modo =="alterar") {
			$cmd = "SELECT * FROM aluno WHERE codaluno = :codaluno";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codaluno', $_REQUEST['cod'], PDO::PARAM_INT);
			$tbl->execute();
			$rowTbl = $tbl->fetch();
		}
		?>
		<form action="<?php echo $this->arquivo; ?>" method="post" role="form">
			<div class="form-group">
				<label for="nome">Nome:</label>
				<input type="text" name="nome" id="nome" value="<?php echo $rowTbl["nome"]; ?>" class="form-control">
				<label for="email">E-mail:</label>
				<input type="text" name="email" id="email" value="<?php echo $rowTbl["email"]; ?>" class="form-control">
				<label for="codcurso">Curso:</label>
				<select name="codtipousuario" id="codtipousuario" class="form-control">
					<option>[Tipo Usuário]</option>
					<?php
					$cmd = "SELECT codtipousuario, descricao FROM tipousuario ORDER BY descricao";
					$tbl = $db->prepare($cmd);
					$tbl->execute();
					while ($row = $tbl->fetch()){
						echo "<option value='$row[codtipousuario]' ";
						if ($row['codtipousuario'] == $rowTbl['codtipousuario'])
							echo " selected";
						echo ">$row[descricao]</option>";
					}
					?>
				</select>
				<label for="senha">Senha:</label>
				<input type="password" name="senha" id="senha" class="form-control">
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

$frm = new Formulario($arquivo, $db);
?>
</div>
</body>
</html>