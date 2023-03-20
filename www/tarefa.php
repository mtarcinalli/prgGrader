<?php
require_once 'header.php';

if (! $db) {
	echo "não abriu bd";
}

$codtarefaturmausuario = (isset($_REQUEST['codtarefaturmausuario']) && is_numeric($_REQUEST['codtarefaturmausuario']) ? $_REQUEST['codtarefaturmausuario'] : null);
$modo = @$_REQUEST['modo'];

function formTarefa($codtarefaturmausuario, $codusuario) {
	?>
	<form action="tarefa.php" method="post" enctype="multipart/form-data" class="form-inline">
		<input type="hidden" name="modo" value="upload">
		<input type="hidden" name="codtarefaturmausuario" value="<?php echo $codtarefaturmausuario; ?>">
		<label for="arquivo">Selecione o arquivo a ser enviado:</label>
		<input type="file" name="arquivo" id="arquivo" accept=".zip" class="form-control">
		<button type="submit" class="btn btn-primary">Enviar arquivo</button>
	</form>
	<?php
}

function downloadModelo($db, $codtarefaturmausuario, $codusuario) {
	$cmd = "SELECT " .
		"tt.codtarefa " .
		"FROM " .
		"tarefaturmausuario tta " .
		"INNER JOIN tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"WHERE codtarefaturmausuario = $codtarefaturmausuario AND tta.codusuario = $codusuario";
	$tblTarefaTurmaUsuario = $db->prepare($cmd);
	$tblTarefaTurmaUsuario->execute();
	$rowTarefaTurmaUsuario = $tblTarefaTurmaUsuario->fetch();
	if (! $rowTarefaTurmaUsuario) {
		return;
	}
	$nomeArquivo = "../uploads/TAREFAS/T$rowTarefaTurmaUsuario[codtarefa]/model.zip";
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

function enviaTarefa($db, $codtarefaturmausuario, $codusuario) {
	$cmd = "select " .
		"('../uploads/CURSO' || tu.codcurso || '/TURMA' || tu.codturma || '/TTURMA' ||  tta.codtarefaturma || '/TTALUNO' || tta.codtarefaturmausuario) AS diretorio , " .
		"tt.codtarefa , " .
		"t.sigla AS tarefasigla, ".
		"t.codplugin, " .
		"tu.sigla AS turmasigla , " .
		"c.sigla AS cursosigla " .
		"FROM " .
		"tarefaturmausuario tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN usuario al ON al.codusuario = tta.codusuario " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"WHERE codtarefaturmausuario = $codtarefaturmausuario and tta.codusuario = $codusuario";
	$tblTarefaTurmaUsuario = $db->prepare($cmd);
	$tblTarefaTurmaUsuario->execute();
	$rowTarefaTurmaUsuario = $tblTarefaTurmaUsuario->fetch();
	$uploaddir = $rowTarefaTurmaUsuario['diretorio'] . "/";
	$uploadfile = $uploaddir . "arquivo.zip";
	$codtarefa = $rowTarefaTurmaUsuario['codtarefa'];
	$codplugin = $rowTarefaTurmaUsuario['codplugin'];
	$files = glob($uploaddir . "*");
	foreach($files as $file){
		if(is_file($file)) {
			unlink($file);
		}
	}
	if (!move_uploaded_file($_FILES['arquivo']['tmp_name'], $uploadfile)) {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao enviar arquivo!</div>";
		return;
	}
	$cmd = "cd $uploaddir && ls && echo '---' && " .
			"ls && " .
			"unzip -j arquivo.zip && " .
			"cp -a ../../../../TAREFAS/T" . $codtarefa .  "/solution/* . && " .
			"cp -a ../../../../CORRETORES/PLUGIN" . $codplugin .  "/corretor/* . && " .
			"bash ./grader.sh";
	$output = trim(shell_exec($cmd));
	$nota = intval(trim(substr($output, strrpos($output, "\n"), -1)));
	$comando = "UPDATE tarefaturmausuario SET " .
			"resultados = :resultados , " .
			"entregas = entregas + 1 , " .
			"dataentrega = date('now'), " .
			"nota = :nota " .
			"WHERE codtarefaturmausuario = :codtarefaturmausuario";
	$query = $db->prepare($comando);
	$query->bindValue(':resultados', $output, PDO::PARAM_STR);
	$query->bindValue(':nota', $nota, PDO::PARAM_INT);
	$query->bindValue(':codtarefaturmausuario', $codtarefaturmausuario, PDO::PARAM_INT);
	if (! $query) {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao salvar informações tarefa [001]!</div>";
		return;
	}
	$ok = $query->execute();
	if (! $ok) {
		echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao salvar informações tarefa! [002]</div>";
		return;
	} else {
		echo "<div class=\"alert alert-success\" role=\"alert\">Informações da avaliação salvas com sucesso!<br>Nota: $nota</div>";
	}
}

function detalheTarefa($db, $codtarefaturmausuario, $codusuario) {
	$cmd = "select " .
		"('../uploads/CURSO' || tu.codcurso || '/TURMA' || tu.codturma || '/TTURMA' ||  tta.codtarefaturma || '/TTALUNO' || tta.codtarefaturmausuario) AS diretorio , " .
		"t.sigla AS tarefasigla, ".
		"tt.codtarefa , " .
		"tt.codturma , " .
		"tu.codcurso , " .
		"tu.descricao AS turma , " .
		"c.descricao AS curso , " .
		"to_char(datainicio, 'DD/MM/YYYY') as datainicio, " .
		"to_char(datafim, 'DD/MM/YYYY') as datafim, " .
		"to_char(dataentrega, 'DD/MM/YYYY') as dataentrega2, " .
		"t.instrucoes , " .
		"p.retorno , " .
		"tta.* " .
		"FROM " .
		"tarefaturmausuario tta " .
		"INNER JOIN tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN plugin p ON p.codplugin = t.codplugin " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN usuario al ON al.codusuario = tta.codusuario " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where codtarefaturmausuario = $codtarefaturmausuario and tta.codusuario = $codusuario";
	$tblTarefaTurmaUsuario = $db->prepare($cmd);
	$tblTarefaTurmaUsuario->execute();
	$rowTarefaTurmaUsuario = $tblTarefaTurmaUsuario->fetch();
	if (! $rowTarefaTurmaUsuario)
		return;
	?>
	<div class="row">
		<div class="col-md-6"><h2>Tarefa:</h2></div>
		<div class="col-md-6 text-right"><a href="tarefa.php">Voltar</a></div>
	  </div>
	<?php
	echo "<table class='table'><tr>" .
			"<td><b>CURSO:</b> $rowTarefaTurmaUsuario[curso]</td>".
			"<td><b>TURMA:</b> $rowTarefaTurmaUsuario[turma]</td>".
			"</tr><tr>" .
			"<td><b>TAREFA:</b> $rowTarefaTurmaUsuario[tarefasigla]</td>" .
			"<td><b>PRAZO DE: </b> $rowTarefaTurmaUsuario[datainicio] ".
			"<b>até</b> $rowTarefaTurmaUsuario[datafim]</td>".
			"</tr><tr>" .
			"<td colspan='2'>" .
			"<b>Instruções:</b><br>" .
			nl2br($rowTarefaTurmaUsuario['instrucoes']) .
			"</td></tr>";

	if (file_exists("../uploads/TAREFAS/T$rowTarefaTurmaUsuario[codtarefa]/model.zip")) {
		echo "<tr><td colspan='2'><b>Modelo:</b> ";
		echo "<a href='?modo=downloadModelo&amp;codtarefaturmausuario=$codtarefaturmausuario'\"><span class=\"glyphicon glyphicon-file\"></a></td></tr>";
	}
	echo "<tr>" .
			"<td><b>Último envio:<b> $rowTarefaTurmaUsuario[dataentrega2]</td>".
			"<td><b>Envios:</b> $rowTarefaTurmaUsuario[entregas]</td>".
			"</tr><tr>" .
			"<td colspan='2'><b>Nota:</b> $rowTarefaTurmaUsuario[nota]</td>".
			"</tr></table>";
	$de = date_create_from_format('d/m/Y', $rowTarefaTurmaUsuario["datainicio"]);
	$ate = date_create_from_format('d/m/Y', $rowTarefaTurmaUsuario["datafim"]);
	$hoje = date_create_from_format('d/m/Y', date('d/m/Y'));
	if ($hoje < $de or $hoje > $ate) {
		echo "<h4>Fora do prazo de envio!</h4>";
	} else {
		formTarefa($codtarefaturmausuario, $codusuario);
	}
	echo "<h3>Resultado último envio:</h3>";
	$res = $rowTarefaTurmaUsuario["resultados"];
	# ocultando saida
	if ($rowTarefaTurmaUsuario['retorno'] == 0) {
		$res = substr($res, 0, strpos($res, "===="));
	}
	echo "<pre>$res</pre>";
	echo "<h3>Arquivos enviados:</h3>";

	echo "Diretório: TURMA$rowTarefaTurmaUsuario[codturma]/TTURMA$rowTarefaTurmaUsuario[codtarefaturma]/TTALUNO$rowTarefaTurmaUsuario[codtarefaturmausuario]\n";
	$diretorio = "../uploads/CURSO$rowTarefaTurmaUsuario[codcurso]/TURMA$rowTarefaTurmaUsuario[codturma]/TTURMA$rowTarefaTurmaUsuario[codtarefaturma]/TTALUNO$rowTarefaTurmaUsuario[codtarefaturmausuario]";


	foreach(preg_split("/((\r?\n)|(\r\n?))/", $rowTarefaTurmaUsuario["resultados"]) as $linha){
		// do stuff with $line
		if ((strpos($linha, "  inflating:") !== FALSE && strpos($linha, ".exe") === FALSE)) {
			echo "\n<tr><td colspan='5'><pre>";
			$arq = trim(substr($linha, 13));
			echo "$arq:<br><br>";
			$output = file_get_contents("$diretorio/$arq");
			$enc = mb_detect_encoding($output);

			if ($output) {
				#echo "ok\n$enc\n";
				$contents = @htmlentities($output, ENT_QUOTES, $enc);
			} else {
				echo "erro: $diretorio/$arq\n\n$output";
			}
			if ($contents)
				echo "\n$contents</pre></td></tr>\n";
			else
				echo "\n $output</pre></td></tr>\n";
		}
	}
}

function listaTarefas($db, $codusuario) {
	$cmd = "select " .
		"codtarefaturmausuario , " .
		"t.sigla AS tarefasigla, ".
		"tu.sigla AS turmasigla , " .
		"c.sigla AS cursosigla , " .
		"to_char(tta.dataentrega, 'DD/MM/YYYY') as dataentrega, " .
		"tta.entregas, tta.nota , tta.notafinal, " .
		"to_char(tt.datainicio, 'DD/MM/YYYY') as datainicio, " .
		"to_char(tt.datafim, 'DD/MM/YYYY') as datafim, " .
		"(tt.datafim >= CURRENT_DATE) as prazo  " .
		"FROM " .
		"tarefaturmausuario tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN usuario al ON al.codusuario = tta.codusuario " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where  tta.codusuario = $codusuario ".
		"ORDER BY tt.codturma desc, t.sigla asc";
	$tblTarefas = $db->prepare($cmd);
	$tblTarefas->execute();
	echo "<table class=\"table table-striped\">" .
			"<tr>" .
			"<th>CURSO</th>" .
			"<th>TURMA</th>" .
			"<th>TAREFA</th>" .
			"<th colspan='2'>PRAZO</th>" .
			"<th>ENTREGA</th>" .
			"<th>TENTATIVAS</th>" .
			"<th>NOTA*</th>" .
			"<th>NOTA FINAL**</th>" .
			"</tr>";
	while ($rowTarefas = $tblTarefas->fetch()) {
		echo "<tr>" .
				"<td>$rowTarefas[cursosigla]</td>".
				"<td>$rowTarefas[turmasigla]</td>".
				"<td>" .
				"<a href='tarefa.php?codtarefaturmausuario=$rowTarefas[codtarefaturmausuario]'>" .
				"$rowTarefas[tarefasigla]</a></td>" .
				"<td " . ($rowTarefas["prazo"] ? "class=\"bg-success\"" : "") . ">$rowTarefas[datainicio]</td>".
				"<td " . ($rowTarefas["prazo"] ? "class=\"bg-success\"" : "") . ">$rowTarefas[datafim]</td>".
				"<td>$rowTarefas[dataentrega]</td>".
				"<td>$rowTarefas[entregas]</td>".
				"<td>$rowTarefas[nota]</td>" .
				"<td>$rowTarefas[notafinal]</td>" .
				"</tr>";
	}
	echo "</table>*NOTA PRELIMINAR CORREÇÃO AUTOMÁTICA<br>**NOTA APÓS CORREÇÃO<br>";
}

function listaNotas($db, $codusuario) {
	echo "<h3>Notas:</h3>";
	$cmd = "SELECT ta.codturma, t.descricao AS turma, c.descricao AS curso ".
			"FROM turmausuario ta ".
			"INNER JOIN turma t ON ta.codturma = t.codturma ".
			"INNER JOIN curso c ON t.codcurso = c.codcurso ".
			"WHERE ta.codusuario = :codusuario ".
			"ORDER BY ta.codturma desc";
    $tblTurmas = $db->prepare($cmd);
    $tblTurmas->bindValue(':codusuario', $codusuario, PDO::PARAM_INT);
    $tblTurmas->execute();
	while ($row = $tblTurmas->fetch(PDO::FETCH_ASSOC)) {
		echo "<h4>Curso: $row[curso] - Turma: $row[turma]</h4>";
		$codturma = $row["codturma"];
		$cmd = "select tt.codtarefaturma, t.sigla ".
				"FROM tarefaturma tt ".
				"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
				"WHERE tt.codturma = :codturma ".
				"ORDER BY t.sigla ASC";
		$tblTarefas = $db->prepare($cmd);
		$tblTarefas->bindValue(':codturma', $codturma, PDO::PARAM_INT);
		$tblTarefas->execute();
		$tarefas = array();
		$avaliacoes = array();
		while ($row = $tblTarefas->fetch(PDO::FETCH_ASSOC)) {
			if ($row["sigla"] == "AVALIAÇÃO-SUB") continue;
			$tarefas[] = $row;
			if (substr($row["sigla"], 0, 6) === "AVALIA") {
				$avaliacoes[] = $row["codtarefaturma"];
			}
		}
		# usuarios
		$cmd = "SELECT a.codusuario, a.nome, a.email ".
					"FROM usuario a ".
					"INNER JOIN turmausuario ta ON a.codusuario = ta.codusuario ".
					"WHERE codturma = :codturma ".
					"AND a.codusuario = :codusuario ".
					"ORDER BY a.nome";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codturma', $codturma, PDO::PARAM_INT);
		$tblAlunos->bindValue(':codusuario', $codusuario, PDO::PARAM_INT);
		$tblAlunos->execute();
		$usuarios=array();
		while ($row = $tblAlunos->fetch(PDO::FETCH_ASSOC)) {
			$usuarios[] = $row;
		}
		# notas
		$cmd = "SELECT tta.codtarefaturma, codusuario, notafinal ".
					"FROM tarefaturmausuario tta ".
					"INNER JOIN tarefaturma tt ON tta.codtarefaturma = tt.codtarefaturma ".
					"WHERE codturma = :codturma ".
					"AND tta.codusuario = :codusuario ".
					"";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codturma', $codturma, PDO::PARAM_INT);
		$tblAlunos->bindValue(':codusuario', $codusuario, PDO::PARAM_INT);
		$tblAlunos->execute();
		$notas = array();
		while ($row = $tblAlunos->fetch(PDO::FETCH_ASSOC)) {
			$notas[$row["codusuario"]][$row["codtarefaturma"]] = $row["notafinal"];
		}
		# exibição
		echo "<table class=\"table\">".
				"<tr>";
		foreach ($tarefas as $tarefa) {
			echo "<th>$tarefa[sigla]</th>";
		}
		echo "<th>FINAL</th></tr>";
		foreach ($usuarios as $usuario) {
			echo "<tr>";
			$notasAv = 0;
			$notasTrab = 0;
			foreach ($tarefas as $tarefa) {
				echo "<td class=\"text-center\">" . $notas[$usuario["codusuario"]][$tarefa["codtarefaturma"]] . "</td>\n";
				if (in_array($tarefa["codtarefaturma"], $avaliacoes)) {
					$notasAv += $notas[$usuario["codusuario"]][$tarefa["codtarefaturma"]];
				} else {
					$notasTrab += $notas[$usuario["codusuario"]][$tarefa["codtarefaturma"]];
				}
			}
			$notaFinal = ceil(  ($notasAv / 2 * 0.7) + ($notasTrab / 7 * 0.3));
			if ($codturma == 9 || $codturma == 8) {
				$notaFinal = ceil(  ($notasAv / 2 * 0.7) + ($notasTrab / 6 * 0.3));
			}
			echo "<td class=\"text-center\">$notaFinal</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

if ($modo == "upload") {
	enviaTarefa($db, $codtarefaturmausuario, $codusuario);
}

if ($modo == "downloadModelo") {
	downloadModelo($db, $codtarefaturmausuario, $codusuario);
}

if ($codtarefaturmausuario) {
	detalheTarefa($db, $codtarefaturmausuario, $codusuario);

} else {
	listaTarefas($db, $codusuario);
	#listaNotas($db, $codusuario);
}
?>
</div>
</body>
</html>
