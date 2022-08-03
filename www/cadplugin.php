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
			$cmd = "UPDATE plugin SET " .
					"descricao = :descricao, " .
					"observacao = :observacao " .
					"WHERE codplugin = :cp";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':cp', $_REQUEST['cp'], PDO::PARAM_INT);
			$acao = "alterar";
			$cp = $_REQUEST['cp'];
			$_REQUEST['cod'] = "";
		} else {
			$cmd = "INSERT INTO plugin " .
				"(descricao, observacao) " .
				"VALUES " .
				"(:descricao, :observacao) ";
			$stmt = $db->prepare($cmd);
			$acao = "incluir";
		}
		$stmt->bindValue(':descricao', $_REQUEST['descricao'], PDO::PARAM_STR);
		$stmt->bindValue(':observacao', $_REQUEST['observacao'], PDO::PARAM_STR);
		$ok = $stmt->execute();
		# creating directory		
		if (! $cp && $ok) {
			$cmd = "SELECT max(codplugin) AS cp FROM plugin";
			$tbl = $db->prepare($cmd);
			$tbl->execute();
			$row = $tbl->fetch();
			$cp = $row["cp"];
			$uploaddir = "../uploads/CORRETORES/PLUGIN$cp/corretor/";
			$cmd = "mkdir -p $uploaddir";
			$output = shell_exec($cmd);
		}
		$uploaddir = "../uploads/CORRETORES/PLUGIN$cp/";
		
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
			$cmd = "cd $uploaddir && " .
				"rm -r corretor/* && " .
				"unzip corretor.zip -d corretor/ ";
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
		$cmd = "DELETE FROM plugin where codplugin = :codplugin";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codplugin', $_REQUEST['cod'], PDO::PARAM_INT);
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
		$cmd = "SELECT * FROM plugin p ORDER BY descricao desc";
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
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codplugin]'\"><span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codplugin]'\"><span class=\"glyphicon glyphicon-pencil\"></span></a> </td>";
			echo "<td>$row[descricao]</td>" .
				"<td>$row[observacao]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}	
	
	function formulario() {
		$db = $this->db;
		if ($this->modo =="alterar") {
			$cmd = "SELECT * FROM plugin WHERE codplugin = :codplugin";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codplugin', $_REQUEST['cod'], PDO::PARAM_INT);
			$tbl->execute();
			$rowTbl = $tbl->fetch();
		}
		?>
		<form action="<?php echo $this->arquivo; ?>" method="post" enctype="multipart/form-data" class="form">
			<div class="form-group">
				<label for="descricao">Corretor:</label>
				<input type="text" name="descricao" id="descricao" value="<?php echo (isset($rowTbl) ? $rowTbl["descricao"] : ""); ?>" class="form-control">
				<label for="arquivo">Arquivo corretor:</label>
				<input type="file" name="arquivo" id="arquivo" accept=".zip" class="form-control">
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