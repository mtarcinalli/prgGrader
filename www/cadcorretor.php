<?php require_once 'header.php';

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
			$cmd = "UPDATE corretor SET " .
					"descricao = :descricao, " .
					"retorno = :retorno, " .
					"observacao = :observacao " .
					"WHERE codcorretor = :cp";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':cp', $_REQUEST['cp'], PDO::PARAM_INT);
			$acao = "alterar";
			$cp = $_REQUEST['cp'];
			$_REQUEST['cod'] = "";
		} else {
			$cmd = "INSERT INTO corretor " .
				"(descricao, retorno, observacao) " .
				"VALUES " .
				"(:descricao, :retorno, :observacao) ";
			$stmt = $db->prepare($cmd);
			$acao = "incluir";
		}
		$retorno = isset($_REQUEST['retorno']);
		$stmt->bindValue(':descricao', $_REQUEST['descricao'], PDO::PARAM_STR);
		$stmt->bindValue(':retorno', $retorno, PDO::PARAM_BOOL);
		$stmt->bindValue(':observacao', $_REQUEST['observacao'], PDO::PARAM_STR);
		$ok = $stmt->execute();
		# creating directory
		if (! isset($cp) && $ok) {
			$cmd = "SELECT max(codcorretor) AS cp FROM corretor";
			$tbl = $db->prepare($cmd);
			$tbl->execute();
			$row = $tbl->fetch();
			$cp = $row["cp"];
			$uploaddir = "../uploads/CORRETORES/CORRETOR$cp/corretor/";
			$cmd = "mkdir -p $uploaddir";
			$output = shell_exec($cmd);
		}
		$uploaddir = "../uploads/CORRETORES/CORRETOR$cp/";

		# uploading
		if ($_FILES['arquivo']['tmp_name']) {
			$uploadfile = $uploaddir . "corretor.zip";
			if(is_file($uploadfile)) {
					unlink($uploadfile);
			}
			if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadfile)) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao enviar arquivo!</div>";
				return;
			}
			# unziping
			$cmd = "cd $uploaddir && pwd && " .
				"rm -rf corretor/* && " .
				"unzip corretor.zip -d corretor/ && ls corretor/";
			echo $cmd;
			$output = trim(shell_exec($cmd));
			echo "<pre>$output</pre>";
			if (! file_exists("$uploaddir/corretor/grader.sh")) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Arquivo grader.sh não encontrado!</div>";
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
		$cmd = "DELETE FROM corretor where codcorretor = :codcorretor";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codcorretor', $_REQUEST['cod'], PDO::PARAM_INT);
		try {
			$ok = $stmt->execute();
		} catch (Exception $e) {
			$ok = false;
			if ($e->getCode() == 23503) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Corretor ainda possui registros relacionados!</div>";
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
		$cmd = "SELECT * FROM corretor p ORDER BY descricao desc";
		$tbl = $db->prepare($cmd);
		$tbl->execute();
		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th></th>" .
				"<th>Corretor</th>" .
				"<th>Observações</th>" .
				"</tr>";
		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codcorretor]'\">X<span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codcorretor]'\">U<span class=\"glyphicon glyphicon-pencil\"></span></a> </td>";
			echo "<td>$row[descricao]</td>" .
				"<td>$row[observacao]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	function formulario() {
		$db = $this->db;
		$retorno = 0;
		if ($this->modo =="alterar") {
			$cmd = "SELECT * FROM corretor WHERE codcorretor = :codcorretor";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codcorretor', $_REQUEST['cod'], PDO::PARAM_INT);
			$tbl->execute();
			$rowTbl = $tbl->fetch();
			$retorno = $rowTbl['retorno'];
			echo '<h2 class="no-margin-bottom">Corretor: alterar</h2>';
		} else {
			echo '<h2 class="no-margin-bottom">Corretor: adicionar</h2>';
		}
		?>

		<form action="<?php echo $this->arquivo; ?>" method="post" enctype="multipart/form-data" class="form">
			<div class="form-group">
				<label for="descricao">Corretor:</label>
				<input type="text" name="descricao" id="descricao" value="<?php echo (isset($rowTbl) ? $rowTbl["descricao"] : ""); ?>" class="form-control">
			</div>
			<div class="form-group">
				<label for="arquivo">Arquivo corretor:</label>
				<input type="file" name="arquivo" id="arquivo" accept=".zip" class="form-control">
			</div>
			<div class="form-group">
				<label for="retorno">Saída da execução:</label>
				<input type="checkbox" id="retorno" name="retorno" value="1" <?php echo ($retorno ? "checked" : ""); ?>>
			</div>
			<div class="form-group">
				<label for="observacao">Observações:</label>
				<textarea name="observacao" id="observacao" class="form-control"><?php echo (isset($rowTbl) ? $rowTbl["observacao"] : ""); ?></textarea>
				<input type="hidden" name="cp" value="<?php echo (isset($_REQUEST['cod']) ? $_REQUEST['cod'] : ""); ?>">
				<input type="hidden" name="modo" value="salvar">
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary form-control">Salvar</button>
			</div>
		</form>
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