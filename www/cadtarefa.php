<?php require_once 'header.php';

if ($codtipousuario > 3) {
	die;
}

class Formulario {
	private $db;
	private $cp;
	private $modo;
	private $arquivo;

	function __construct($arquivo, $db) {
		$this->modo = (isset($_REQUEST["modo"]) ? $_REQUEST["modo"] : "");
		$this->cp = (isset($_REQUEST['cod']) && is_numeric($_REQUEST['cod']) ? $_REQUEST['cod'] : null);
		$this->arquivo = $arquivo;
		$this->db = $db;
		$this->acao();
	}
	
	function salvar() {
		$db = $this->db;
		$cp = 0;
		if ($_REQUEST['cp'] != "") {
			$cmd = "UPDATE tarefa SET " .
					"codplugin = :codplugin, " .
					"descricao = :descricao, " .
					"sigla = :sigla, " .
					"instrucoes = :instrucoes, " .
					"observacao = :observacao " .
					"WHERE codtarefa = :cp";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':cp', $_REQUEST['cp'], PDO::PARAM_INT);
			$acao = "alterar";
			$_REQUEST['cod'] = "";
			$cp = $_REQUEST['cp'];
		} else {
			$cmd = "INSERT INTO tarefa " .
				"(codplugin, descricao, sigla, instrucoes, observacao) " .
				"VALUES " .
				"(:codplugin, :descricao, :sigla, :instrucoes, :observacao) ";
			$stmt = $db->prepare($cmd);
			$acao = "incluir";
		}
		$stmt->bindValue(':codplugin', $_REQUEST['codplugin'], PDO::PARAM_INT);
		$stmt->bindValue(':descricao', $_REQUEST['descricao'], PDO::PARAM_STR);
		$stmt->bindValue(':sigla', $_REQUEST['sigla'], PDO::PARAM_STR);
		$stmt->bindValue(':instrucoes', $_REQUEST['instrucoes'], PDO::PARAM_STR);
		$stmt->bindValue(':observacao', $_REQUEST['observacao'], PDO::PARAM_STR);
		$ok = $stmt->execute();
		if (! $cp && $ok) {
			$cmd = "SELECT max(codtarefa) AS cp FROM tarefa";
			$tbl = $db->prepare($cmd);
			$tbl->execute();
			$row = $tbl->fetch();
			$cp = $row["cp"];
			# criando diretório solução
			$dir = "../uploads/TAREFAS/T$cp/solution";
			$cmd = "mkdir -p $dir";
			$output = shell_exec($cmd);
			# criando diretório modelo
			$dir = "../uploads/TAREFAS/T$cp/model";
			$cmd = "mkdir -p $dir";
			$output = shell_exec($cmd);
		}
		# upload solução
		if ($_FILES['arquivo']['tmp_name']) {
			$uploadfile = "../uploads/TAREFAS/T$cp/solution.zip";
			if(is_file($uploadfile)) {
					unlink($uploadfile);
			}
			if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadfile)) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao enviar arquivo!</div>";
				return;
			}
			$cmd = "rm -rf ../uploads/TAREFAS/T$cp/solution/* && " .
				"unzip ../uploads/TAREFAS/T$cp/solution.zip -d ../uploads/TAREFAS/T$cp/solution/";
			$output = shell_exec($cmd);
		}
		# upload modelo
		if ($_FILES['arquivoaluno']['tmp_name']) {
			$uploadfile = "../uploads/TAREFAS/T$cp/model.zip";
			if(is_file($uploadfile)) {
					unlink($uploadfile);
			}
			if (!move_uploaded_file($_FILES['arquivoaluno']['tmp_name'], $uploadfile)) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao enviar arquivo!</div>";
				return;
			}
			$cmd = "rm -rf ../uploads/TAREFAS/T$cp/model/* && " .
				"unzip ../uploads/TAREFAS/T$cp/model.zip -d ../uploads/TAREFAS/T$cp/model/";
			$output = shell_exec($cmd);
		}
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
		try {
			$ok = $stmt->execute();
		} catch (Exception $e) {
			$ok = false;
			if ($e->getCode() == 23503) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Tarefa ainda possui registros relacionados!</div>";
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
			echo "<td>";
			echo (file_exists("../uploads/TAREFAS/T$row[codtarefa]/solution.zip") ? "<a href='?modo=downloadSolucao&amp;cod=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-exclamation-sign\"></a>" : "");
			echo "</td>";
			echo "<td>";
			echo (file_exists("../uploads/TAREFAS/T$row[codtarefa]/model.zip") ? "<a href='?modo=downloadModelo&amp;cod=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-file\"></a>" : "");
			echo "</td>";
			echo "<td>$row[descricao]</td>";
			echo "<td>$row[sigla]</td>";
			echo "<td>" . nl2br($row['instrucoes']) . "</td>";
			echo "<td>" . nl2br($row['observacao']) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<h4>Legenda:</h4>";
		echo "<span class=\"glyphicon glyphicon-trash\"></span> Excluir<br>";
		echo "<span class=\"glyphicon glyphicon-pencil\"></span> Alterar<br>";
		echo "<span class=\"glyphicon glyphicon-send\"></span> Atribuir para alunos<br>";
		echo "<span class=\"glyphicon glyphicon-exclamation-sign\"></span> Download arquivo de solução<br>";
		echo "<span class=\"glyphicon glyphicon-file\"></span> Download arquivo de modelo<br>";
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
		<form action="<?php echo $this->arquivo; ?>" method="post" enctype="multipart/form-data" role="form">
			<div class="form-group">
				<label for="descricao">Tarefa:</label>
				<input type="text" name="descricao" id="descricao" value="<?php echo (isset($rowTbl) ? $rowTbl["descricao"] : ""); ?>" class="form-control">
				<label for="sigla">Sigla:</label>
				<input type="text" name="sigla" id="sigla" value="<?php echo (isset($rowTbl) ? $rowTbl["sigla"] : ""); ?>" class="form-control">
				<label for="instrucoes">Instruções:</label>
				<textarea name="instrucoes" id="instrucoes" class="form-control"><?php echo (isset($rowTbl) ? $rowTbl["instrucoes"]: ""); ?></textarea>
				<label for="codplugin">Corretor:</label>
				<select name="codplugin" id="codplugin" class="form-control">
					<option>[Corretor]</option>
					<?php
					$cmd = "SELECT codplugin, descricao FROM plugin ORDER BY descricao";
					$tbl = $db->prepare($cmd);
					$tbl->execute();
					while ($row = $tbl->fetch()){
						echo "<option value='$row[codplugin]' ";
						if (isset($rowTbl))
							if ($row['codplugin'] == $rowTbl['codplugin'])
								echo " selected";
						echo ">$row[descricao]</option>";
					}
					?>
				</select>
				<label for="arquivo">Arquivo solução:</label>
				<input type="file" name="arquivo" id="arquivo" accept=".zip" class="form-control">
				<label for="arquivo">Arquivo modelo aluno:</label>
				<input type="file" name="arquivoaluno" id="arquivoaluno" accept=".zip" class="form-control">
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

	function download() {
		if (! $this->cp) {
			return false;
		}
		if ($this->modo == "downloadSolucao") {
			$nomeArquivo = "../uploads/TAREFAS/T$this->cp/solution.zip";
		}
		if ($this->modo == "downloadModelo") {
			$nomeArquivo = "../uploads/TAREFAS/T$this->cp/model.zip";
		}
		if (! file_exists($nomeArquivo)) {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao baixar arquivo! [inexistente]</div>";	
			return false;
		}
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: 0");
		header('Content-Disposition: attachment; filename="'.basename($nomeArquivo).'"');
		header('Content-Length: ' . filesize($nomeArquivo));
		header('Pragma: public');
		flush();
		readfile($nomeArquivo);
		die();
	}
	
	function acao() {
		if ($this->modo == "salvar") {
			$this->salvar();
		}
		if ($this->modo == "exclui") {
			$this->excluir();
		}
		if ($this->modo == "downloadModelo" || $this->modo == "downloadSolucao") {
			$this->download();
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