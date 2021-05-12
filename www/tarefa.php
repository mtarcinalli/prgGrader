<?php require_once 'header.php';

#error_reporting(E_ALL);

$db = new SQLite3('../db/pgrader.db');
if (! $db)
	echo "não abriu bd";

#$codaluno = 1;
#$_REQUEST['codaluno'];
$codtarefaturmaaluno = @$_REQUEST['codtarefaturmaaluno'];
$modo = @$_REQUEST['modo'];


function formTarefa($codtarefaturmaaluno) {
	?>
	<form action="tarefa.php" method="post" enctype="multipart/form-data" class="form-inline">
				<input type="hidden" name="modo" value="upload">
				<input type="hidden" name="codtarefaturmaaluno" value="<?php echo $codtarefaturmaaluno; ?>">
				<label for="arquivo">Selecione o arquivo a ser enviado:
				<input type="file" name="arquivo" id="arquivo" accept="*.zip" class="form-control">
				<button type="submit" class="btn btn-primary">Enviar arquivo</button>
	</form>
	<?php
}

function enviaTarefa($db, $codtarefaturmaaluno, $codaluno) {
	$cmd = "select " .
		"('../uploads/' || c.sigla || '/' || tu.sigla || '/' ||  t.sigla || tt.codtarefaturma ||   '/TTA' || tta.codtarefaturmaaluno) AS diretorio , " .
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

	$tblTarefaTurmaAluno = $db->query($cmd);
	$rowTarefaTurmaAluno = $tblTarefaTurmaAluno->fetchArray(SQLITE3_ASSOC);

	
	$uploaddir = $rowTarefaTurmaAluno['diretorio'] . "/";
	$uploadfile = $uploaddir . "arquivo.zip";

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
			"unzip arquivo.zip && " .
			"ls && " .
			"cxxtestgen --error-printer -o runner01.cpp solution.h && " .
			"g++ -o runner01 runner01.cpp && " .
			"./runner01";
	#echo $cmd;
	$output = shell_exec($cmd);
	#echo "<pre>$output</pre>";

	$output = substr($output, 0, -1);
	#echo "-4:" . substr($output, -4);
	#echo "<br>";
	#echo "-1:" . substr($output, -2);

	if (substr($output, -1) == "%") {
		$nota = 50;
	} elseif (substr($output, -5) == "..OK!") {
		$nota = 100;
	} else {
		$nota = 0;
	}

	
	# Running cxxtest tests (2 tests)..OK!
	# Success rate: 50%
	
	$comando = "UPDATE tarefaturmaaluno SET " .
			"resultados = '$output' , " .
			"entregas = entregas + 1 , " .
			"dataentrega = date('now'), " .
			"nota = $nota " .
			"WHERE codtarefaturmaaluno = $codtarefaturmaaluno";
	$query = $db->prepare($comando);
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
		"tta.dataentrega, tta.entregas, tta.nota , " .
		"tt.datainicio, tt.datafim " .
		"from " .
		"tarefaturmaaluno tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN aluno al ON al.codaluno = tta.codaluno " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where  tta.codaluno = $codaluno";
	#echo "cmd: $cmd";
	$tblTarefas = $db->query($cmd);

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


	while ($rowTarefas = $tblTarefas->fetchArray(SQLITE3_ASSOC)) {
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
	echo "<h2>Tarefa:</h2>";
	$cmd = "select " .
		"('../uploads/' || c.sigla || '/' || tu.sigla || '/' ||  t.sigla || tt.codtarefaturma ||   '/TTA' || tta.codtarefaturmaaluno) AS diretorio , " .
		"t.sigla AS tarefasigla, ".
		"tu.sigla AS turmasigla , " .
		"c.sigla AS cursosigla , " .
		"tta.*, " .
		"* from " .
		"tarefaturmaaluno tta " .
		"inner join tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
		"INNER JOIN tarefa t ON t.codtarefa = tt.codtarefa " .
		"INNER JOIN turma tu ON tu.codturma = tt.codturma " .
		"INNER JOIN aluno al ON al.codaluno = tta.codaluno " .
		"INNER JOIN curso c ON c.codcurso = tu.codcurso " .
		"where codtarefaturmaaluno = $codtarefaturmaaluno and tta.codaluno = $codaluno";
	#echo "cmd: $cmd";

	$tblTarefaTurmaAluno = $db->query($cmd);
	$rowTarefaTurmaAluno = $tblTarefaTurmaAluno->fetchArray(SQLITE3_ASSOC);

	echo "<table class='table'><tr>" .
			"<td><b>CURSO:</b> $rowTarefaTurmaAluno[cursosigla]</td>".
			"<td><b>TURMA:</b> $rowTarefaTurmaAluno[turmasigla]</td>".
			"<td><b>TAREFA:</b> $rowTarefaTurmaAluno[tarefasigla]</td>" .
			"<td>$rowTarefaTurmaAluno[datainicio]</td>".
			"<td>$rowTarefaTurmaAluno[datafim]</td>".
			"<td>$rowTarefaTurmaAluno[dataentrega]</td>".
			"<td>$rowTarefaTurmaAluno[entregas]</td>".
			"<td>$rowTarefaTurmaAluno[nota]</td>".			
			"</tr></table>";

	formTarefa($codtarefaturmaaluno);
	
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