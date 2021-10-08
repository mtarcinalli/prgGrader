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
			"ls && " .
			"cxxtestgen --error-printer --have-eh -o runner01.cpp solution.h 2>&1 && " .
			"g++ -o runner01 runner01.cpp 2>&1 && " .
			"./runner01 2>&1";
	#echo $cmd;
	#$txt = exec($cmd, $output, $return_var);
	#var_dump($output);
	#var_dump($return_var);
	#echo $txt;
	#echo '</pre>';
	$output = shell_exec($cmd);
	#echo "<pre>$output</pre>";

	#$output = substr($output, 0, -1);
	#echo "-4:" . substr($output, -4);
	#echo "<br>";
	#echo "-1:" . substr($output, -2);

	if (substr($output, -1) == "%") {
		$nota = substr( substr($output, -3), 0, -1);
	} elseif (substr($output, -5) == "..OK!") {
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



function listaTarefas($db, $codaluno) { 
	$cmd = "select " .
		"codtarefaturmaaluno , " .
		"t.sigla AS tarefasigla, ".
		"tu.sigla AS turmasigla , " .
		"c.sigla AS cursosigla , " .
		"to_char(tta.dataentrega, 'DD/MM/YYYY') as dataentrega, " .
		"tta.entregas, tta.nota , " .
		"to_char(tt.datainicio, 'DD/MM/YYYY') as datainicio, " .
		"to_char(tt.datafim, 'DD/MM/YYYY') as datafim " .
		"FROM " .
		"tarefaturmaaluno tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN aluno al ON al.codaluno = tta.codaluno " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where  tta.codaluno = $codaluno";
	#echo "cmd: $cmd";
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
			"</tr>";


	while ($rowTarefas = $tblTarefas->fetch()) {
		echo "<tr>" .
				"<td>$rowTarefas[cursosigla]</td>".
				"<td>$rowTarefas[turmasigla]</td>".
				"<td>" .
				"<a href='tarefa.php?codtarefaturmaaluno=$rowTarefas[codtarefaturmaaluno]'>" .
				"$rowTarefas[tarefasigla]</a></td>" .
				"<td>$rowTarefas[datainicio]</td>".
				"<td>$rowTarefas[datafim]</td>".
				"<td>$rowTarefas[dataentrega]</td>".
				"<td>$rowTarefas[entregas]</td>".
				"<td>$rowTarefas[nota]</td>".			
				"</tr>";
	}
	echo "</table>*NOTA PRELIMINAR CORREÇÃO AUTOMÁTICA<br>";
}

function detalheTarefa($db, $codtarefaturmaaluno, $codaluno) {
	$cmd = "select " .
		"('../uploads/CURSO' || tu.codcurso || '/TURMA' || tu.codturma || '/TTURMA' ||  tta.codtarefaturma || '/TTALUNO' || tta.codtarefaturmaaluno) AS diretorio , " .
		"t.sigla AS tarefasigla, ".
		"tu.descricao AS turma , " .
		"c.descricao AS curso , " .

		"to_char(datainicio, 'DD/MM/YYYY') as datainicio, " .
		"to_char(datafim, 'DD/MM/YYYY') as datafim, " .
		"to_char(dataentrega, 'DD/MM/YYYY') as dataentrega2, " .
		#"STRFTIME('%d/%m/%Y', datainicio) as datainicio , " .
		#"STRFTIME('%d/%m/%Y', datafim) as datafim , " .
		#"STRFTIME('%d/%m/%Y', dataentrega) as dataentrega2 , " .
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
	#echo "cmd: $cmd";

	$tblTarefaTurmaAluno = $db->prepare($cmd);
	$tblTarefaTurmaAluno->execute();
	$rowTarefaTurmaAluno = $tblTarefaTurmaAluno->fetch();
	if (! $rowTarefaTurmaAluno)
		return;

	echo "<h2>Tarefa:</h2>";
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
	echo "<pre>$rowTarefaTurmaAluno[resultados]</pre>";


}	



if ($modo == "upload") {
	enviaTarefa($db, $codtarefaturmaaluno, $codaluno);
}


listaTarefas($db, $codaluno);

if ($codtarefaturmaaluno) {
	detalheTarefa($db, $codtarefaturmaaluno, $codaluno);
	
}

?>

</div>
</body>
</html>
