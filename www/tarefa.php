<?php
require_once 'header.php';

#$db = new SQLite3('../db/pgrader.db');
if (! $db)
	echo "não abriu bd";

$codtarefaturmaaluno = @$_REQUEST['codtarefaturmaaluno'];
$modo = @$_REQUEST['modo'];

function formTarefa($codtarefaturmaaluno, $codaluno) {
	?>
	<form action="tarefa.php" method="post" enctype="multipart/form-data" class="form-inline">
		<input type="hidden" name="modo" value="upload">
		<input type="hidden" name="codtarefaturmaaluno" value="<?php echo $codtarefaturmaaluno; ?>">
		<label for="arquivo">Selecione o arquivo a ser enviado:</label>
		<input type="file" name="arquivo" id="arquivo" accept="*.zip" class="form-control">
		<button type="submit" class="btn btn-primary">Enviar arquivo</button>
	</form>
	<?php
}

function enviaTarefa($db, $codtarefaturmaaluno, $codaluno) {
	$cmd = "select " .
		"('../uploads/CURSO' || tu.codcurso || '/TURMA' || tu.codturma || '/TTURMA' ||  tta.codtarefaturma || '/TTALUNO' || tta.codtarefaturmaaluno) AS diretorio , " .
		"tt.codtarefa , " .
		"t.sigla AS tarefasigla, ".
		"tu.sigla AS turmasigla , " .
		"c.sigla AS cursosigla " .
		#"tta.* " .
		"FROM " .
		"tarefaturmaaluno tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN aluno al ON al.codaluno = tta.codaluno " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where codtarefaturmaaluno = $codtarefaturmaaluno and tta.codaluno = $codaluno";
	#echo "cmd: $cmd";

	$tblTarefaTurmaAluno = $db->prepare($cmd);
	$tblTarefaTurmaAluno->execute();
	$rowTarefaTurmaAluno = $tblTarefaTurmaAluno->fetch();

	
	$uploaddir = $rowTarefaTurmaAluno['diretorio'] . "/";
	$uploadfile = $uploaddir . "arquivo.zip";
	$codtarefa = $rowTarefaTurmaAluno['codtarefa'];

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

	#echo '<pre>';
	$cmd = "cd $uploaddir && ls && echo '---' && " .
			#"rm !(arquivo.zip) -f && " .
			"ls && " .
			"unzip -j arquivo.zip && " .
			"cp ../../../../TAREFAS/T" . $codtarefa .  "/solution.h . && " .
			"cxxtestgen --error-printer --have-eh -o runner01.cpp solution.h 2>&1 && " .
			"g++ -o runner01 runner01.cpp 2>&1 && " .
			"echo '====' && " .
			"timeout 8s ./runner01 2>&1";
	#echo $cmd;
	#$txt = exec($cmd, $output, $return_var);
	#var_dump($output);
	#var_dump($return_var);
	#echo $txt;
	#echo '</pre>';
	$output = trim(shell_exec($cmd));
	#echo "<pre>$output</pre>";

	#$output = substr($output, 0, -1);
	#echo "-4:" . substr($output, -4);
	#echo "<br>";
	#echo "-1:" . substr($output, -2);
	#echo "kk" . substr($output, -5) . "kk";

	if (substr($output, -1) == "%") {
		$nota = substr( substr($output, -3), 0, -1);
	} elseif (substr($output, -4) == ".OK!") {
		$nota = 100;
	} else {
		$nota = 0;
	}

	#echo "<pre>-$nota-</pre>";
	
	# Running cxxtest tests (2 tests)..OK!
	# Success rate: 50%
	
	$comando = "UPDATE tarefaturmaaluno SET " .
			"resultados = :resultados , " .
			"entregas = entregas + 1 , " .
			"dataentrega = date('now'), " .
			"nota = :nota " .
			"WHERE codtarefaturmaaluno = :codtarefaturmaaluno";
	$query = $db->prepare($comando);
	$query->bindValue(':resultados', $output, PDO::PARAM_STR);
	$query->bindValue(':nota', $nota, PDO::PARAM_INT);
	$query->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
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




function detalheTarefa($db, $codtarefaturmaaluno, $codaluno) {
	$cmd = "select " .
		"('../uploads/CURSO' || tu.codcurso || '/TURMA' || tu.codturma || '/TTURMA' ||  tta.codtarefaturma || '/TTALUNO' || tta.codtarefaturmaaluno) AS diretorio , " .
		"t.sigla AS tarefasigla, ".
		"tt.codturma , " .
		"tu.codcurso , " .
		"tu.descricao AS turma , " .
		"c.descricao AS curso , " .
		"to_char(datainicio, 'DD/MM/YYYY') as datainicio, " .
		"to_char(datafim, 'DD/MM/YYYY') as datafim, " .
		"to_char(dataentrega, 'DD/MM/YYYY') as dataentrega2, " .
		"t.instrucoes , " .
		"tta.* " .
		"FROM " .
		"tarefaturmaaluno tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN aluno al ON al.codaluno = tta.codaluno " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where codtarefaturmaaluno = $codtarefaturmaaluno and tta.codaluno = $codaluno";
	$tblTarefaTurmaAluno = $db->prepare($cmd);
	$tblTarefaTurmaAluno->execute();
	$rowTarefaTurmaAluno = $tblTarefaTurmaAluno->fetch();
	if (! $rowTarefaTurmaAluno)
		return;
	?>
	<div class="row">
		<div class="col-md-6"><h2>Tarefa:</h2></div>
		<div class="col-md-6 text-right"><a href="tarefa.php">Voltar</a></div>
	  </div>
	<?php
	echo "<table class='table'><tr>" .
			"<td><b>CURSO:</b> $rowTarefaTurmaAluno[curso]</td>".
			"<td><b>TURMA:</b> $rowTarefaTurmaAluno[turma]</td>".
			"</tr><tr>" .
			"<td><b>TAREFA:</b> $rowTarefaTurmaAluno[tarefasigla]</td>" .
			"<td><b>PRAZO DE: </b> $rowTarefaTurmaAluno[datainicio] ".
			"<b>até</b> $rowTarefaTurmaAluno[datafim]</td>".
			"</tr><tr>" .
			"<td colspan='2'><b>Instruções:</b><br>" . nl2br($rowTarefaTurmaAluno['instrucoes']) . "</td>" .
			"</tr><tr>" .
			"<td><b>Último envio:<b> $rowTarefaTurmaAluno[dataentrega2]</td>".
			"<td><b>Envios:</b> $rowTarefaTurmaAluno[entregas]</td>".
			"</tr><tr>" .
			"<td colspan='2'><b>Nota:</b> $rowTarefaTurmaAluno[nota]</td>".			
			"</tr></table>";
	$de = date_create_from_format('d/m/Y', $rowTarefaTurmaAluno["datainicio"]);
	$ate = date_create_from_format('d/m/Y', $rowTarefaTurmaAluno["datafim"]);
	$hoje = date_create_from_format('d/m/Y', date('d/m/Y'));
	if ($hoje < $de or $hoje > $ate) {
		echo "<h4>Fora do prazo de envio!</h4>";
	} else {
		formTarefa($codtarefaturmaaluno, $codaluno);
	}
	echo "<h3>Resultado último envio:</h3>";
	$res = $rowTarefaTurmaAluno["resultados"];
	# ocultando saida cxxtest
	#$res = substr($res, 0, strpos($res, "===="));
	echo "<pre>$res</pre>";
	echo "<h3>Arquivos enviados:</h3>";

	echo "Diretório: TURMA$rowTarefaTurmaAluno[codturma]/TTURMA$rowTarefaTurmaAluno[codtarefaturma]/TTALUNO$rowTarefaTurmaAluno[codtarefaturmaaluno]\n";
	$diretorio = "../uploads/CURSO$rowTarefaTurmaAluno[codcurso]/TURMA$rowTarefaTurmaAluno[codturma]/TTURMA$rowTarefaTurmaAluno[codtarefaturma]/TTALUNO$rowTarefaTurmaAluno[codtarefaturmaaluno]";


	foreach(preg_split("/((\r?\n)|(\r\n?))/", $rowTarefaTurmaAluno["resultados"]) as $linha){
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

function listaTarefas($db, $codaluno) { 
	$cmd = "select " .
		"codtarefaturmaaluno , " .
		"t.sigla AS tarefasigla, ".
		"tu.sigla AS turmasigla , " .
		"c.sigla AS cursosigla , " .
		"to_char(tta.dataentrega, 'DD/MM/YYYY') as dataentrega, " .
		"tta.entregas, tta.nota , tta.notafinal, " .
		"to_char(tt.datainicio, 'DD/MM/YYYY') as datainicio, " .
		"to_char(tt.datafim, 'DD/MM/YYYY') as datafim, " .
		"(tt.datafim >= CURRENT_DATE) as prazo  " .
		"FROM " .
		"tarefaturmaaluno tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN aluno al ON al.codaluno = tta.codaluno " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where  tta.codaluno = $codaluno ".
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
				"<a href='tarefa.php?codtarefaturmaaluno=$rowTarefas[codtarefaturmaaluno]'>" .
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

function listaNotas($db, $codaluno) {
	echo "<h3>Notas:</h3>";
	$cmd = "SELECT ta.codturma, t.descricao AS turma, c.descricao AS curso ".
			"FROM turmaaluno ta ".
			"INNER JOIN turma t ON ta.codturma = t.codturma ".
			"INNER JOIN curso c ON t.codcurso = c.codcurso ".
			"WHERE ta.codaluno = :codaluno ".
			"ORDER BY ta.codturma desc";
    $tblTurmas = $db->prepare($cmd);
    $tblTurmas->bindValue(':codaluno', $codaluno, PDO::PARAM_INT);
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
		# alunos
		$cmd = "SELECT a.codaluno, a.nome, a.email ".
					"FROM aluno a ".
					"INNER JOIN turmaaluno ta ON a.codaluno = ta.codaluno ".
					"WHERE codturma = :codturma ".
					"AND a.codaluno = :codaluno ".
					"ORDER BY a.nome";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codturma', $codturma, PDO::PARAM_INT);
		$tblAlunos->bindValue(':codaluno', $codaluno, PDO::PARAM_INT);
		$tblAlunos->execute();
		$alunos=array();
		while ($row = $tblAlunos->fetch(PDO::FETCH_ASSOC)) {
			$alunos[] = $row;
		}
		# notas
		$cmd = "SELECT tta.codtarefaturma, codaluno, notafinal ".
					"FROM tarefaturmaaluno tta ".
					"INNER JOIN tarefaturma tt ON tta.codtarefaturma = tt.codtarefaturma ".
					"WHERE codturma = :codturma ".
					"AND tta.codaluno = :codaluno ".
					"";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codturma', $codturma, PDO::PARAM_INT);
		$tblAlunos->bindValue(':codaluno', $codaluno, PDO::PARAM_INT);
		$tblAlunos->execute();
		$notas = array();
		while ($row = $tblAlunos->fetch(PDO::FETCH_ASSOC)) {
			$notas[$row["codaluno"]][$row["codtarefaturma"]] = $row["notafinal"];
		}
		# exibição
		echo "<table class=\"table\">".
				"<tr>";
		foreach ($tarefas as $tarefa) {
			echo "<th>$tarefa[sigla]</th>";
		}
		echo "<th>FINAL</th></tr>";
		foreach ($alunos as $aluno) {
			echo "<tr>";
			$notasAv = 0;
			$notasTrab = 0;
			foreach ($tarefas as $tarefa) {
				echo "<td class=\"text-center\">" . $notas[$aluno["codaluno"]][$tarefa["codtarefaturma"]] . "</td>\n";
				if (in_array($tarefa["codtarefaturma"], $avaliacoes)) {
					$notasAv += $notas[$aluno["codaluno"]][$tarefa["codtarefaturma"]];
				} else {
					$notasTrab += $notas[$aluno["codaluno"]][$tarefa["codtarefaturma"]];
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
	enviaTarefa($db, $codtarefaturmaaluno, $codaluno);
}

if ($codtarefaturmaaluno) {
	detalheTarefa($db, $codtarefaturmaaluno, $codaluno);
	
} else {
	listaTarefas($db, $codaluno);
	#listaNotas($db, $codaluno);
}
?>
</div>
</body>
</html>
