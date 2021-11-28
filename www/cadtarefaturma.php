<?php require_once 'header.php';



class Form {
	
	private $db;
	private $modo;
	private $arquivo;
	
	
	function __construct($arquivo, $db) {
		$this->modo = $_REQUEST["modo"];
		$this->arquivo = $arquivo;
		$this->db = $db;
			#new SQLite3('../db/pgrader.db');
		#if (! $this->db)
		#	echo "não abriu bd";
		$this->acao();
	}
	
	
	function salvar() {
		$db = $this->db;
		
		$cmd = "INSERT INTO tarefaturma " .
			"(codtarefa, codturma, datainicio, datafim, observacao) " .
			"VALUES " .
			"(:codtarefa, :codturma, :datainicio, :datafim, :observacao) ";
		$stmt = $db->prepare($cmd);

		$stmt->bindValue(':codtarefa', $_REQUEST['codtarefa']);
		$stmt->bindValue(':codturma', $_REQUEST['codturma']);
		$stmt->bindValue(':datainicio', $_REQUEST['datainicio']);
		$stmt->bindValue(':datafim', $_REQUEST['datafim']);
		$stmt->bindValue(':observacao', $_REQUEST['observacao']);
		$ok = $stmt->execute();

		$cmd = "SELECT max(codtarefaturma) FROM tarefaturma";
		$tbl = $db->prepare($cmd);
		$tbl->execute();
		$row = $tbl->fetch();
		$codtarefaturma = $row[0];

		$cmd = "SELECT codcurso FROM turma WHERE codturma = :codturma";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codturma', $_REQUEST['codturma'], SQLITE3_INTEGER);
		$tbl->execute();
		$row = $tbl->fetch();
		$codcurso = $row[0];

		$cmd = "SELECT codaluno FROM turmaaluno WHERE codturma = :codturma";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codturma', $_REQUEST['codturma']);
		$tblAlunos->execute();
		while ($rowAluno = $tblAlunos->fetch()) {
			$cmd = "INSERT INTO tarefaturmaaluno " .
					"(codtarefaturma, codaluno) " .
					"VALUES " .
					"(:codtarefaturma, :codaluno)";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':codtarefaturma', $codtarefaturma, SQLITE3_INTEGER);
			$stmt->bindValue(':codaluno', $rowAluno['codaluno'], SQLITE3_INTEGER);
			$ok = $stmt->execute();

			$cmd = "SELECT max(codtarefaturmaaluno) FROM tarefaturmaaluno";
			$tbl = $db->prepare($cmd);
			$tbl->execute();
			$row = $tbl->fetch();
			$codtarefaturmaaluno = $row[0];

			$cmd = "mkdir -p ../uploads/CURSO$codcurso/TURMA$_REQUEST[codturma]/TTURMA$codtarefaturma/TTALUNO$codtarefaturmaaluno";
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
		$cmd = "DELETE FROM tarefaturma where codtarefaturma = :codtarefaturma";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefaturma', $_REQUEST['cod'], SQLITE3_INTEGER);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}
	
	
	
	
	function formulario() {
		$db = $this->db;
		
		$cmd = "SELECT t.* FROM tarefa t WHERE codtarefa = :codtarefa";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codtarefa', $_REQUEST['codtarefa'], SQLITE3_INTEGER);
		$tbl->execute();
		$rowTbl = $tbl->fetch();

		
		echo "<table class='table'>" .
				"<tr>" .
				"<th>Tarefa:</th>" .
				"<td>$rowTbl[descricao]</td>" .
				"<th>Sigla:</th>" .
				"<td>$rowTbl[sigla]</td>" .
				"<td><a href='cadtarefa.php'>Voltar</a></td>" .
				"</tr><tr>" .
				"<th>Instruções:</th>" .
				"<td colspan='4'>" . nl2br($rowTbl['instrucoes']) . "</td>" .
				"</tr><tr>" .
				"<th>Observações:</th>" .
				"<td colspan='4'>" . nl2br($rowTbl['observacao']) . "</td>" .
				"</tr>" .
				"</table>";
		?>
		<hr>
		<h3>Atribuir tarefa para turma:</h3>
		<form action="cadtarefaturma.php" method="post" role="form" class="form-horizontal">
			<div class="form-group">
				<label for="codturma" class="col-sm-1 control-label">Turma:</label>
				<div class="col-sm-3">
					<select name="codturma" id="codturma" class="form-control" placeholder="Turma">
						<option>[Turma]</option>
						<?php
						$cmd = "SELECT codturma, descricao, sigla FROM turma ORDER BY sigla DESC";
						$tbl = $db->prepare($cmd);
						$tbl->execute();
						while ($row = $tbl->fetch()){
							echo "<option value='$row[codturma]' ";
							if ($row['codturma'] == $rowTbl['codturma'])
								echo " selected";
							echo ">$row[sigla] - $row[descricao]</option>";
						}
						?>
					</select>
				</div>
				<label for="datainicio" class="col-sm-1 control-label">Início:</label>
				<div class="col-sm-3">
					<input type="date" name="datainicio" id="datainicio" class="form-control" placeholder="Data de início">
				</div>
				<label for="datafim" class="col-sm-1 control-label">Término:</label>
				<div class="col-sm-3">
					<input type="date" name="datafim" id="datafim" class="form-control" placeholder="Data de término">
				</div>
			</div>
			<div class="form-group">			
				<label for="observacao" class="col-sm-2 control-label">Observações:</label>
				<div class="col-sm-9">
					<input type="text" name="observacao" id="observacao" class="form-control">
				</div>
				<div class="col-sm-1">
					<input type="hidden" name="codtarefa" value="<?php echo $_REQUEST['codtarefa']; ?>">
					<input type="hidden" name="modo" value="salvar">			
					<button type="submit" class="btn btn-primary">Salvar</button>
				</div>
			</div>
		</form>
		
		<hr>
		
		
		<?php
		
		
	}
	
	
	
	function listar() {
		$db = $this->db;
		$cmd = "SELECT " .
				"tt.codturma , " .
				"tt.codtarefa , " .
				"tt.codtarefaturma , " .
				"to_char(datainicio, 'DD/MM/YY') as datainicio , " .
				"to_char(datafim, 'DD/MM/YY') as datafim , " .
				"tt.observacao , " .
				"t.descricao AS turma " .
				"FROM tarefaturma tt " .
				"INNER JOIN turma t ON t.codturma = tt.codturma " .
				"WHERE tt.codtarefa = :codtarefa ";
				#"ORDER BY nome asc";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codtarefa', $_REQUEST['codtarefa']);
		$tbl->execute();

		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th>Cod</th>" .
				"<th>Turma</th>" .
				"<th>Início</th>" .
				"<th>Fim</th>" .
				"<th>Observações</th>" .
				"</tr>";


		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?modo=exclui&amp;cod=$row[codtarefaturma]&amp;codtarefa=$_REQUEST[codtarefa]'\">del</a> </td>";
			echo "<td>$row[codtarefaturma]</td>";
			echo "<td>$row[turma]($row[codturma])</td>";
			echo "<td>$row[datainicio]</td>";
			echo "<td>$row[datafim]</td>";
			echo "<td>$row[observacao]</td>";
			echo "</tr>";
			?>
			<tr>
				<td></td>
				<td colspan="5">
					<a href="#alunos<?php echo $row['codtarefaturma']; ?>" data-toggle="collapse"><span class="ion-ios-arrow-down"></span>Alunos:</a></h4>
					<div id="alunos<?php echo $row['codtarefaturma']; ?>" class="card-body collapse">
						<?php
						$cmd = "SELECT " .
								"tta.codtarefaturmaaluno , " .
								"a.nome , " .
								"to_char(dataentrega, 'DD/MM/YY') as dataentrega , " .
								"tta.entregas , " .
								"tta.resultados , " .
								"tta.nota " .
								"FROM tarefaturmaaluno tta " .
								"INNER JOIN aluno a ON tta.codaluno = a.codaluno " .
								"WHERE codtarefaturma =  :codtarefaturma " . 
								"ORDER BY nome ASC";
						$tblAlunos = $db->prepare($cmd);
						$tblAlunos->bindValue(':codtarefaturma', $row['codtarefaturma'], SQLITE3_INTEGER);
						$tblAlunos->execute();
						echo "<table class=\"table table-striped\" style=\"table-layout:fixed; word-wrap:break-word;\">" .
							"<tr>" .
							"<th>Cod</th>" .
							"<th>Aluno</th>" .
							"<th>Data</th>" .
							"<th>Entregas</th>" .
							"<th>Nota</th>" .
							"</tr>";

						while ($rowAluno = $tblAlunos->fetch()) {
							echo "<tr>" .
									"<td>" .
									"<a href=\"cadtarefaaluno.php?cp=$rowAluno[codtarefaturmaaluno]&amp;codtarefa=$_REQUEST[codtarefa]\">" .
									"$rowAluno[codtarefaturmaaluno]" .
									"</a>" .
									"</td>" .
									"<td>$rowAluno[nome]</td>" .
									"<td>$rowAluno[dataentrega]</td>" .
									"<td>$rowAluno[entregas]</td>" .
									"<td>$rowAluno[nota]</td>" .
									"</tr>";
							if (false && $rowAluno["resultados"]) {
								echo "<tr><td colspan='5'><pre>";
								echo "Diretório: TURMA$row[codturma]/TTURMA$row[codtarefaturma]/TTALUNO$rowAluno[codtarefaturmaaluno]\n";
								echo $rowAluno["resultados"];
								
								echo "</pre></td></tr>";
							}
						}
						echo "</table>";
						?>
					</div>
				</td>
			<?php
			
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
			$this->importarAlunos();
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
