<?php require_once 'header.php';

require_once 'formulaevaluator.php';

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
		$this->acao();
	}

	function salvar() {
		$db = $this->db;
		if (isset($_REQUEST['codtarefaturma'])) {
			# alterando
			$cmd = "UPDATE tarefaturma " .
				"SET ".
				"datainicio = :datainicio, " .
				"datafim = :datafim, " .
				"observacao = :observacao " .
				"WHERE codtarefaturma = :codtarefaturma";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':datainicio', $_REQUEST['datainicio']);
			$stmt->bindValue(':datafim', $_REQUEST['datafim']);
			$stmt->bindValue(':observacao', $_REQUEST['observacao'], PDO::PARAM_STR);
			$stmt->bindValue(':codtarefaturma', $_REQUEST['codtarefaturma'], PDO::PARAM_INT);
			$ok = $stmt->execute();
		} else {
			# inserindo
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
			$tbl->bindValue(':codturma', $_REQUEST['codturma'], PDO::PARAM_INT);
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
				$stmt->bindValue(':codtarefaturma', $codtarefaturma, PDO::PARAM_INT);
				$stmt->bindValue(':codaluno', $rowAluno['codaluno'], PDO::PARAM_INT);
				$ok = $stmt->execute();

				$cmd = "SELECT max(codtarefaturmaaluno) FROM tarefaturmaaluno";
				$tbl = $db->prepare($cmd);
				$tbl->execute();
				$row = $tbl->fetch();
				$codtarefaturmaaluno = $row[0];

				$cmd = "mkdir -p ../uploads/CURSO$codcurso/TURMA$_REQUEST[codturma]/TTURMA$codtarefaturma/TTALUNO$codtarefaturmaaluno";
				$output = shell_exec($cmd);
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
		$cmd = "SELECT count(*) FROM tarefaturmaaluno WHERE codtarefaturma = :codtarefaturma AND entregas > 0";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefaturma', $_REQUEST['cod'], PDO::PARAM_INT);
		$stmt->execute();
		$qtdRegs = $stmt->fetchColumn();
		if ($qtdRegs == 0) {
			$cmd = "DELETE FROM tarefaturmaaluno WHERE codtarefaturma = :codtarefaturma";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':codtarefaturma', $_REQUEST['cod'], PDO::PARAM_INT);
			try {
				$ok = $stmt->execute();
			} catch (Exception $e) {
				$ok = false;
				echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro (atribuição)!</div>";
				return;
			}
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Algum aluno já realizou envio de tarefa!</div>";
			return;
		}
		$cmd = "DELETE FROM tarefaturma where codtarefaturma = :codtarefaturma";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefaturma', $_REQUEST['cod'], PDO::PARAM_INT);
		try {
			$ok = $stmt->execute();
		} catch (Exception $e) {
			$ok = false;
			if ($e->getCode() == 23503) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Atribuição de tarefa ainda possui registros relacionados!</div>";
				return;
			}
		}
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}

	function excluirAluno() {
		$db = $this->db;
		$cmd = "DELETE FROM tarefaturmaaluno where codtarefaturmaaluno = :codtarefaturmaaluno";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefaturmaaluno', $_REQUEST['codtarefaturmaaluno'], PDO::PARAM_INT);
		try {
			$ok = $stmt->execute();
		} catch (Exception $e) {
			$ok = false;
		}
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Registro excluído com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao excluir registro!</div>";
		}
	}


	function processarNotas() {
		$db = $this->db;
		$ok = true;
		$formula = $_POST['formula'];

		$lexer = new FormulaLexer();
		$tokens = $lexer->tokenize($formula);
		
		#echo "<pre>";
		#echo "Fórmula: $formula\n";
		foreach ($_POST as $key => $value) {
			if (substr($key, 0 ,3) == "nf-") {
				$cod = explode("-", $key);
				$codtarefaturmaaluno = $cod[1];
				$cmd = "SELECT codtarefaturmaaluno, codtarefaturma, codaluno, nota, notallm, plagiomoss, geradoia, notafinal FROM tarefaturmaaluno WHERE codtarefaturmaaluno = :codtarefaturmaaluno";
				$stmt = $db->prepare($cmd);
				$stmt->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
				$stmt->execute();
				$row = $stmt->fetch();
				#print_r($row);
				#echo "cod: $codtarefaturmaaluno, nota: $row[nota], notallm: $row[notallm], plagiomoss: $row[plagiomoss], geradoia: $row[geradoia], notafinal: $row[notafinal]<br>";
				#break;

				$evaluator = new FormulaEvaluator($tokens, [
					'nota' => ($row["nota"] ? $row["nota"] : 0),
					'nota_llm' => ($row["notallm"] ? $row["notallm"] : 0),
					'moss' => ($row["plagiomoss"] ? $row["plagiomoss"] : 0),
					'ia' => ($row["geradoia"] ? $row["geradoia"] : 0),
				]);

				$notaFinal = $evaluator->evaluate();
				#echo $notaFinal;
				#echo "<br>";

				$cmd = "UPDATE tarefaturmaaluno SET notafinal = :notafinal where codtarefaturmaaluno = :codtarefaturmaaluno";
				$stmt = $db->prepare($cmd);
				$stmt->bindValue(':notafinal', $notaFinal, PDO::PARAM_INT);
				$stmt->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
				$ok1 = $stmt->execute();
				if (! $ok1) {
					$ok = false;
				}
				#break;
			}
		}
		#echo "</pre>";
		#echo "<pre>";
		#print_r($_REQUEST);
		#echo "</pre>";
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Notas finais processadas com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro processar notas finais!</div>";
		}
	}

	function salvarNotas() {
		$db = $this->db;
		$ok = true;
		foreach ($_POST as $key => $value) {
			if (substr($key, 0 ,3) == "nf-") {
				if ($value != "") {
					$cod = explode("-", $key);
					$cmd = "UPDATE tarefaturmaaluno SET notafinal = :notafinal where codtarefaturmaaluno = :codtarefaturmaaluno";
					$stmt = $db->prepare($cmd);
					$stmt->bindValue(':notafinal', $value, PDO::PARAM_INT);
					$stmt->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
					$ok1 = $stmt->execute();
					if (! $ok1) {
						$ok = false;
					}
				}
			}
		}
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Notas finais salvas com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro salvar notas finais!</div>";
		}
	}

	function formulario($headerMode = false) {
		$db = $this->db;
		echo "<h3>Atribuição de tarefas por turma:</h3>";
		$codtarefa = 0;
		if (isset($_REQUEST['codtarefa']) && $_REQUEST['codtarefa']) {
			$codtarefa = $_REQUEST['codtarefa'];
			$cmd = "SELECT t.* FROM tarefa t WHERE codtarefa = :codtarefa";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codtarefa', $_REQUEST['codtarefa'], PDO::PARAM_INT);
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
					"</table>" .
					"<hr>";
		}
		if ($this->modo == "alterar" or $this->modo == "moss" or $this->modo == "llm") {
			$cmd = "SELECT " .
				"tt.* " .
				", to_char(datainicio, 'DD/MM/YYYY') as datainicio " .
				", to_char(datafim, 'DD/MM/YYYY') as datafim " .
				", datainicio as datainicioform " .
				", datafim as datafimform " .
				", t.descricao AS turma " .
				", t.sigla AS siglaturma " .
				", c.descricao AS curso " .
				", c.sigla AS siglacurso " .
				"FROM tarefaturma tt " .
				"INNER JOIN turma t ON t.codturma = tt.codturma " .
				"INNER JOIN curso c on c.codcurso = t.codcurso " .
				"WHERE codtarefaturma = :codtarefaturma";
			$tbl = $db->prepare($cmd);
			$tbl->bindValue(':codtarefaturma', $_REQUEST['cod'], PDO::PARAM_INT);
			$tbl->execute();
			$rowTurma = $tbl->fetch();
			
		}
		if ($headerMode) {
			echo "<h4>Atribuido para:</h4>";
			echo "<table class='table'>" .
				"<tr>" .
				"<th>Curso:</th>" .
				"<td>$rowTurma[curso] - $rowTurma[siglacurso]</td>" .
				"<th>Turma:</th>" .
				"<td>$rowTurma[turma] - $rowTurma[siglaturma]</td>" .
				"</tr><tr>" .
				"<th>Início:</th>" .
				"<td>$rowTurma[datainicio]</td>" .
				"<th>Fim:</th>" .
				"<td>$rowTurma[datafim]</td>" .
				"</tr><tr>" .
				"<th>Observações:</th>" .
				"<td colspan='4'>" . nl2br($rowTbl['observacao']) . "</td>" .
				"</tr>" .
				"</table>";
			return;
		}
		?>
		<form action="cadtarefaturma.php" method="post" role="form" class="form-horizontal">
			<div class="form-group">
				<label for="codturma" class="col-sm-1 control-label">Turma:</label>
				<div class="col-sm-2">
					<select name="codturma" id="codturma" class="form-control" placeholder="Turma" <?php echo ($this->modo == "alterar" ? "disabled" : "")   ?>>
						<option>Turma</option>
						<?php
						$cmd = "SELECT codturma, descricao, sigla FROM turma ORDER BY sigla DESC";
						$tbl = $db->prepare($cmd);
						$tbl->execute();
						while ($row = $tbl->fetch()){
							echo "<option value='$row[codturma]' ";
							if (isset($rowTurma))
								if ($row['codturma'] == @$rowTbl['codturma'] || $row['codturma'] == $rowTurma['codturma'])
									echo " selected";
							echo ">$row[sigla] - $row[descricao]</option>";
						}
						?>
					</select>
				</div>
				<label for="codtarefa" class="col-sm-1 control-label">Tarefa:</label>
				<div class="col-sm-2">
					<select name="codtarefa" id="codtarefa" class="form-control" placeholder="Tarefa" <?php echo ($this->modo == "alterar" ? "disabled" : "")   ?>>
						<option>Tarefa</option>
						<?php
						$cmd = "SELECT codtarefa, descricao, sigla FROM tarefa ORDER BY sigla DESC";
						$tbl = $db->prepare($cmd);
						$tbl->execute();
						while ($row = $tbl->fetch()){
							echo "<option value='$row[codtarefa]' ";
							if (isset($rowTurma)) {
								if ($row['codtarefa'] == @$rowTbl['codtarefa'] || $row['codtarefa'] == $rowTurma['codtarefa'])
									echo " selected";
							} else {
								if ($row['codtarefa'] == $codtarefa)
									echo " selected";
							}
							echo ">$row[sigla] - $row[descricao]</option>";
						}
						?>
					</select>
				</div>



				<label for="datainicio" class="col-sm-1 control-label">Início:</label>
				<div class="col-sm-2">
					<input type="date" name="datainicio" id="datainicio" class="form-control" placeholder="Data de início" value="<?php echo (isset($rowTurma) ? $rowTurma['datainicioform'] : ""); ?>">
				</div>
				<label for="datafim" class="col-sm-1 control-label">Término:</label>
				<div class="col-sm-2">
					<input type="date" name="datafim" id="datafim" class="form-control" placeholder="Data de término" value="<?php echo (isset($rowTurma) ? $rowTurma['datafimform'] : ""); ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="observacao" class="col-sm-2 control-label">Observações:</label>
				<div class="col-sm-8">
					<textarea name="observacao" id="observacao" class="form-control"><?php echo (isset($rowTurma) ? $rowTurma["observacao"] : ""); ?></textarea>
				</div>
				<div class="col-sm-2">
					<?php
					if ($this->modo == "alterar") {
						echo "<input type=\"hidden\" name=\"codtarefaturma\" value=\"$rowTurma[codtarefaturma]\">";
					}
					?>
					<input type="hidden" name="modo" value="salvar">
					<button type="submit" class="btn btn-primary">Salvar</button>
					<a href="cadtarefaturma.php" class="btn btn-default">Cancelar</a>
				</div>
			</div>
		</form>
		<hr>
		<?php
	}

	function listar() {
		$db = $this->db;

		$codcursoFiltro = isset($_REQUEST['codcurso']) && $_REQUEST['codcurso'] !== '' ? (int)$_REQUEST['codcurso'] : 0;
		$codturmaFiltro = isset($_REQUEST['codturma']) && $_REQUEST['codturma'] !== '' ? (int)$_REQUEST['codturma'] : 0;
		$codtarefaFiltro = isset($_REQUEST['codtarefa']) && $_REQUEST['codtarefa'] !== '' ? (int)$_REQUEST['codtarefa'] : 0;
		?>
		<?php if ($this->modo != "novo" && $this->modo != "alterar") { ?>
			<p><a href="cadtarefaturma.php?modo=novo" class="btn btn-success">Nova atribuição</a></p>
		<?php } ?>
		<form action="cadtarefaturma.php" method="get" role="form" class="form-horizontal">
			<div class="form-group">
				<label for="filtro-codcurso" class="col-sm-1 control-label">Curso:</label>
				<div class="col-sm-3">
					<select name="codcurso" id="filtro-codcurso" class="form-control" onchange="document.getElementById('filtro-codturma').value=''; this.form.submit()">
						<option value="">Todos os cursos</option>
						<?php
						$cmdCursos = "SELECT codcurso, descricao, sigla FROM curso ORDER BY descricao";
						$tblCursos = $db->prepare($cmdCursos);
						$tblCursos->execute();
						while ($rowCurso = $tblCursos->fetch()) {
							echo "<option value='$rowCurso[codcurso]'";
							if ($rowCurso['codcurso'] == $codcursoFiltro) echo " selected";
							echo ">$rowCurso[sigla] - $rowCurso[descricao]</option>";
						}
						?>
					</select>
				</div>

				<label for="filtro-codturma" class="col-sm-1 control-label">Turma:</label>
				<div class="col-sm-3">
					<select name="codturma" id="filtro-codturma" class="form-control" onchange="this.form.submit()">
						<option value="">Todas as turmas</option>
						<?php
						$cmdTurmas = "SELECT codturma, codcurso, descricao, sigla FROM turma";
						if ($codcursoFiltro > 0) {
							$cmdTurmas .= " WHERE codcurso = :codcurso";
						}
						$cmdTurmas .= " ORDER BY sigla";
						$tblTurmas = $db->prepare($cmdTurmas);
						if ($codcursoFiltro > 0) {
							$tblTurmas->bindValue(':codcurso', $codcursoFiltro, PDO::PARAM_INT);
						}
						$tblTurmas->execute();
						while ($rowTurmaFiltro = $tblTurmas->fetch()) {
							echo "<option value='$rowTurmaFiltro[codturma]'";
							if ($rowTurmaFiltro['codturma'] == $codturmaFiltro) echo " selected";
							echo ">$rowTurmaFiltro[sigla] - $rowTurmaFiltro[descricao]</option>";
						}
						?>
					</select>
				</div>

				<label for="filtro-codtarefa" class="col-sm-1 control-label">Tarefa:</label>
				<div class="col-sm-3">
					<select name="codtarefa" id="filtro-codtarefa" class="form-control">
						<option value="">Todas as tarefas</option>
						<?php
						$cmdTarefasFiltro = "SELECT codtarefa, descricao, sigla FROM tarefa ORDER BY sigla DESC";
						$tblTarefasFiltro = $db->prepare($cmdTarefasFiltro);
						$tblTarefasFiltro->execute();
						while ($rowTarefaFiltro = $tblTarefasFiltro->fetch()) {
							echo "<option value='$rowTarefaFiltro[codtarefa]'";
							if ($rowTarefaFiltro['codtarefa'] == $codtarefaFiltro) echo " selected";
							echo ">$rowTarefaFiltro[sigla] - $rowTarefaFiltro[descricao]</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-1 col-sm-11">
					<button type="submit" class="btn btn-primary">Filtrar</button>
					<a href="cadtarefaturma.php" class="btn btn-default">Limpar</a>
				</div>
			</div>
		</form>
		<hr>
		<?php

		$cmd = "SELECT " .
				"tt.codturma , " .
				"tt.codtarefa , " .
				"tt.codtarefaturma , " .
				"to_char(datainicio, 'DD/MM/YYYY') as datainicio , " .
				"to_char(datafim, 'DD/MM/YYYY') as datafim , " .
				"tt.observacao , " .
				"t.descricao AS turma , " .
				"ta.descricao AS tarefa , " .
				"c.descricao AS curso " .
				"FROM tarefaturma tt " .
				"INNER JOIN turma t ON t.codturma = tt.codturma " .
				"INNER JOIN tarefa ta ON ta.codtarefa = tt.codtarefa " .
				"INNER JOIN curso c ON c.codcurso = t.codcurso ";

		$where = array();
		$params = array();
		if ($codcursoFiltro > 0) {
			$where[] = "c.codcurso = :codcurso";
			$params[':codcurso'] = $codcursoFiltro;
		}
		if ($codturmaFiltro > 0) {
			$where[] = "tt.codturma = :codturma";
			$params[':codturma'] = $codturmaFiltro;
		}
		if ($codtarefaFiltro > 0) {
			$where[] = "tt.codtarefa = :codtarefa";
			$params[':codtarefa'] = $codtarefaFiltro;
		}
		if (!empty($where)) {
			$cmd .= "WHERE " . implode(" AND ", $where) . " ";
		}
		$cmd .= "ORDER BY c.descricao, t.descricao, ta.descricao";

		$tbl = $db->prepare($cmd);
		foreach ($params as $param => $value) {
			$tbl->bindValue($param, $value, PDO::PARAM_INT);
		}
		$tbl->execute();
		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th></th>" .
				"<th></th>" .
				"<th></th>" .
				"<th></th>" .
				#"<th>Cod</th>" .
				"<th>Curso</th>" .
				"<th>Turma</th>" .
				"<th>Tarefa</th>" .
				"<th>Início</th>" .
				"<th>Fim</th>" .
				#"<th>Observações</th>" .
				"</tr>";
		while ($row = $tbl->fetch()) {
			echo "<tr>";
			echo "<td><a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) " .
			"window.location='?modo=exclui&amp;cod=$row[codtarefaturma]&amp;codtarefa=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-trash\"></span></a> </td>";
			echo "<td><a href='?modo=alterar&amp;cod=$row[codtarefaturma]&amp;codtarefa=$row[codtarefa]'\"><span class=\"glyphicon glyphicon-pencil\"></span></a> </td>";
			echo "<td><a href='?modo=moss&amp;cod=$row[codtarefaturma]&amp;codtarefa=$row[codtarefa]'\">MOSS</a> </td>";
			echo "<td><a href='?modo=llm&amp;cod=$row[codtarefaturma]&amp;codtarefa=$row[codtarefa]'\">LLM</a> </td>";
			#echo "<td>$row[codtarefaturma]</td>";
			echo "<td>$row[curso]</td>";
			echo "<td>$row[turma]</td>";
			echo "<td>$row[tarefa]</td>";
			echo "<td>$row[datainicio]</td>";
			echo "<td>$row[datafim]</td>";
			#echo "<td>$row[observacao]</td>";
			echo "</tr>";
			?>
			<form action="cadtarefaturma.php" method="post">
			<tr>
				<td></td>
				<td></td>
				<td colspan="6"><a href="#alunos<?php echo $row['codtarefaturma']; ?>" data-toggle="collapse"><span class="ion-ios-arrow-down"></span>Alunos:</a></h4></td>
				<td>
					<input type="hidden" name="codcurso" value="<?php echo $codcursoFiltro; ?>">
					<input type="hidden" name="codturma" value="<?php echo $codturmaFiltro; ?>">	
					<input type="hidden" name="codtarefa" value="<?php echo $codtarefaFiltro; ?>">
					<input type="hidden" name="modo" value="salvarNotas">
					<button type="submit" class="btn btn-secondary" onclick="this.form.elements['modo'].value='salvarNotas'">Salvar Notas</button>
				</td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td colspan="7">
					<div id="alunos<?php echo $row['codtarefaturma']; ?>" class="card-body collapse">
						<?php
						$cmd = "SELECT " .
								"tta.codtarefaturmaaluno , " .
								"a.nome , " .
								"to_char(datahoraentrega, 'DD/MM/YYYY HH24:MI:SS') as dataentrega , " .
								"tta.entregas , " .
								"tta.resultados , " .
								"tta.notafinal, " .
								"tta.notallm, " .
								"tta.plagiomoss, " .
								"tta.geradoia, " .
								"tta.nota " .
								"FROM tarefaturmaaluno tta " .
								"INNER JOIN aluno a ON tta.codaluno = a.codaluno " .
								"WHERE codtarefaturma =  :codtarefaturma " .
								"ORDER BY nome ASC";
						$tblAlunos = $db->prepare($cmd);
						$tblAlunos->bindValue(':codtarefaturma', $row['codtarefaturma'], PDO::PARAM_INT);
						$tblAlunos->execute();
						echo "";
						echo "<table class=\"table table-striped\" style=\"table-layout:fixed; word-wrap:break-word;\">" .
							"<tr>" .
							"<th>Fórmula:</th>" .
							"<th colspan=\"5\"><input type=\"text\" id=\"formula\" name=\"formula\" class=\"form-control\" value=\"=SE(OU(MOSS > 40; IA > 80); 0; MAX(NOTA; NOTA_LLM))\"></th>" .
							"<th colspan=\"2\"><button type=\"submit\" class=\"btn btn-secondary\" onclick=\"this.form.elements['modo'].value='processarNotas'\">Processar Notas</button></th>" .
							"<th colspan=\"2\"><button type=\"submit\" class=\"btn btn-secondary\" onclick=\"this.form.elements['modo'].value='salvarNotas'\">Salvar Notas</button></th>" .
							"</tr>" .
							"<tr>" .
							"<th></th>" .
							"<th></th>" .
							"<th>Aluno</th>" .
							"<th>Entrega</th>" .
							"<th>Tentativas</th>" .
							"<th>Nota</th>" .
							"<th>Nota LLM</th>" .
							"<th>MOSS</th>" .
							"<th>IA</th>" .
							"<th>Nota Final</th>" .
							"</tr>";
						while ($rowAluno = $tblAlunos->fetch()) {
							echo "<tr>" .
									"<td>" .
									"<a href='#' OnClick=\"JavaScript: if (confirm('Confirma exclus&atilde;o?')) window.location='?" .
									"modo=excluirAluno&amp;cod=$row[codtarefaturma]&amp;codtarefa=$row[codtarefa]&amp;codtarefaturmaaluno=$rowAluno[codtarefaturmaaluno]'\">" .
									"<span class=\"glyphicon glyphicon-trash\"></span></a> </td>" .
									"<td>" .
									"<a href=\"cadtarefaaluno.php?cp=$rowAluno[codtarefaturmaaluno]&amp;codtarefa=$row[codtarefa]\">" .
									"<span class=\"glyphicon glyphicon-search\"></span>" .
									#"$rowAluno[codtarefaturmaaluno]" .
									"</a>" .
									"</td>" .
									"<td>$rowAluno[nome]</td>" .
									"<td>$rowAluno[dataentrega]</td>" .
									"<td>$rowAluno[entregas]</td>" .
									"<td>$rowAluno[nota]</td>" .
									"<td>$rowAluno[notallm]</td>" .
									"<td>$rowAluno[plagiomoss]%</td>" .
									"<td>$rowAluno[geradoia]%</td>" .
									"<td><input type=\"text\" id=\"nf-$rowAluno[codtarefaturmaaluno]\" name=\"nf-$rowAluno[codtarefaturmaaluno]\" value=\"$rowAluno[notafinal]\"  class=\"form-control\"></td>" .
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
			</tr>
			</form>
			<?php
		}
		echo "</table>";
	}

	function moss() {
		$db = $this->db;
		echo "<h4>Verificação de plágio - MOSS</h4>";
		$codtarefa = $_REQUEST['codtarefa'];
		$codtarefaturma = $_REQUEST['cod'];
		# buscando codturma e codcurso da tarefa
		$cmd = "SELECT " .
			"tt.codturma " .
			", tt.metadados " .
			", t.codcurso " .
			"FROM tarefaturma tt " .
			"INNER JOIN turma t ON t.codturma = tt.codturma " .
			"WHERE codtarefaturma = :codtarefaturma";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codtarefaturma', $codtarefaturma, PDO::PARAM_INT);
		$tbl->execute();
		$rowTurma = $tbl->fetch();
		$codturma = $rowTurma['codturma'];
		$codcurso = $rowTurma['codcurso'];
		// echo "<pre>";
		// print_r($rowTurma);
		// echo "</pre>";
		# montando dicionario com alunos da turma
		$cmd = "SELECT " .
			"tta.codtarefaturmaaluno " .
			", a.nome " .
			"FROM tarefaturmaaluno tta " .
			"INNER JOIN aluno a ON a.codaluno = tta.codaluno " .
			"WHERE tta.codtarefaturma = :codtarefaturma";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codtarefaturma', $codtarefaturma, PDO::PARAM_INT);
		$tblAlunos->execute();
		$alunos = array();
		$alunosMoss = array();
		while ($rowAluno = $tblAlunos->fetch()) {
			$alunos[$rowAluno['codtarefaturmaaluno']] = $rowAluno['nome'];
			$alunosMoss[$rowAluno['codtarefaturmaaluno']] = 0;
		}
		// echo "<pre>";
		// print_r($alunos);
		// echo "</pre>";
		# executando o Moss

		# verificar se existem mossUrl no campo metadados da tabela tarefaturma, se existir exibir o link mossUrl e o resultado mossResultado, se não existir executar o moss
		$metadados = json_decode($rowTurma['metadados'], true);
		# alterar o if para verificar o reprocessar, se existir o reprocessar no request, apagar o mossUrl e mossResultado do metadados e executar o moss novamente
		if (isset($_REQUEST['reprocessar'])) {
			unset($metadados['mossUrl']);
			unset($metadados['mossResultado']);
		}
		
		if (isset($metadados['mossUrl'])) {
			$resUrl = $metadados['mossUrl'];
			$resultado = $metadados['mossResultado'];
			echo("Endereço resultado do Moss:<br><a href=\"$resUrl\" target=\"_blank\">$resUrl</a>");
			# exibir data e hora do resultado do moss, que está no campo mossDataHora do metadados formatado como d/m/Y H:i:s
			echo "<br>Data e hora do resultado do Moss: " . date('d/m/Y H:i:s', strtotime($metadados['mossDataHora']));

			# exibir link para reprocessar o moss, que vai apagar o mossUrl e mossResultado do metadados e executar o moss novamente
			echo "<br><a href='?modo=moss&amp;cod=$codtarefaturma&amp;codtarefa=$codtarefa&amp;reprocessar=1' class='btn btn-warning'>Reprocessar Moss</a>";
			#echo "<pre>";
			#echo $resultado;
			#echo "</pre>";
			#return;
		} else {

			$filesPath = "../uploads/CURSO$codcurso/TURMA$codturma/TTURMA$codtarefaturma/*/*.c";
			#echo "<p>Arquivos a serem enviados para MOSS: $filesPath</p>";
			include("moss.php");
			$userid = "382132825"; // Enter your MOSS userid
			$moss = new MOSS($userid);
			$moss->setLanguage('c');
			$moss->addByWildcard($filesPath);
			#$moss->addBaseFile('../uploads/CURSO1/TURMA1/TTURMA7/TTALUNO167/main.c');
			$moss->setCommentString("This is a test");

			$resUrl = trim($moss->send());
			#$resUrl = "http://moss.stanford.edu/results/8/2364435252706";
			#$resUrl = "http://moss.stanford.edu/results/1/1490901169340";
			#$resUrl = "http://moss.stanford.edu/results/1/9670971460613";
			echo("Endereço resultado do Moss:<br><a href=\"$resUrl\" target=\"_blank\">$resUrl</a>");
			

			# download url recebida em $resUrl e exibir conteudo do arquivo moss.log
			# try file_get_contents, se retornar 404 dar um tempo e tentar novamente, até 5 vezes
			$maxRetries = 5;
			$retryCount = 0;
			while ($retryCount < $maxRetries) {
				$logContent = @file_get_contents($resUrl);
				if ($logContent !== false) {
					break;
				}
				$retryCount++;
				sleep(2); // Espera 2 segundos antes de tentar novamente
			}
			if ($logContent === false) {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao acessar o resultado do MOSS. Tente novamente mais tarde.</div>";
				return;
			}

			$tags_para_remover = '/<\/?(html|head|title|body)[^>]*>/i';
			$logFormatado = preg_replace($tags_para_remover, '', $logContent);
			#echo "<h4>Log formatado:</h4>";
			#echo "<pre>";
			#echo $logFormatado;
			#echo "</pre>";



			$resultado = preg_replace_callback(
				#'/\.\.\/uploads\/[^\/]+\/[^\/]+\/[^\/]+\/TTALUNO(\d+)/',
				'/(\.\.\/uploads\/[^\/]+\/[^\/]+\/[^\/]+\/TTALUNO(\d+))(\/[^\s<]+\s*\((\d+)%\))/',
				function ($matches) use ($alunos, &$alunosMoss) {
					#$idAluno = $matches[1]; // Pega apenas os dígitos após TTALUNO
					$idAluno = $matches[2];          // ID do aluno (ex: 114)
                	$sufixoArquivo = $matches[3];    // Ex: /main_(4).c (96%)
                	$porcentagem = (int)$matches[4]; // Percentual inteiro (ex: 96)

					// Atualiza o dicionário se a porcentagem encontrada for maior que a atual
					if (isset($alunosMoss[$idAluno]) && $porcentagem > $alunosMoss[$idAluno]) {
						$alunosMoss[$idAluno] = $porcentagem;
					}

                	// Substitui apenas o prefixo do caminho pelo nome do aluno, mantendo o arquivo e a %
                	$nomeOuCaminho = isset($alunos[$idAluno]) ? $alunos[$idAluno] : $matches[1];
                	return $nomeOuCaminho . $sufixoArquivo;
					//if isset($alunosMoss[$idAluno])) {
					//	
					//}
					// Se encontrar no dicionário, substitui pelo Nome. Se não, mantém o texto original.
					#return isset($alunos[$idAluno]) ? $alunos[$idAluno] : $matches[0];
				},
				$logFormatado
			);
			$resultado = preg_replace('/<A\s+HREF=/i', '<A TARGET="_blank" HREF=', $resultado);

			#echo "<pre>";
			#echo $logFormatado;
			#print_r($alunosMoss);
			#echo "</pre>";

			# Atualiza o maior percentual de plágio de cada aluno na tabela tarefaturmaaluno
			$cmdUpdateAluno = "UPDATE tarefaturmaaluno 
							SET plagiomoss = :plagiomoss 
							WHERE codtarefaturmaaluno = :codtarefaturmaaluno";
			$stmtUpdateAluno = $db->prepare($cmdUpdateAluno);

			foreach ($alunosMoss as $codtarefaturmaaluno => $maiorPorcentagem) {
				$stmtUpdateAluno->bindValue(':plagiomoss', $maiorPorcentagem, PDO::PARAM_INT);
				$stmtUpdateAluno->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
				$stmtUpdateAluno->execute();
			}			


			# salvar resultado, data e hora atual e mossurl no banco de dados no campo metadados (que é json) chaves mossUrl, mossDataHora e mossResultado data tabela tarefaturma
			date_default_timezone_set('America/Sao_Paulo');
			$metadados = array(
				'mossUrl' => $resUrl,
				'mossDataHora' => date('Y-m-d H:i:s'),
				'mossResultado' => $resultado
			);
			$metadadosJson = json_encode($metadados);
			$cmd = "UPDATE tarefaturma SET metadados = :metadados WHERE codtarefaturma = :codtarefaturma";
			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':metadados', $metadadosJson, PDO::PARAM_STR);
			$stmt->bindValue(':codtarefaturma', $codtarefaturma, PDO::PARAM_INT);
			$ok = $stmt->execute();
			# se ok exibir mensagem de sucesso, se não exibir mensagem de erro
			if ($ok) {
				echo "<div class=\"alert alert-success\" role=\"alert\">Resultado do MOSS salvo com sucesso!</div>";
			} else {
				echo "<div class=\"alert alert-danger\" role=\"alert\">Erro ao salvar resultado do MOSS!</div>";
			}
			echo "<a href='?modo=moss&amp;cod=$codtarefaturma&amp;codtarefa=$codtarefa&amp;reprocessar=1' class='btn btn-warning'>Reprocessar Moss</a>";
		}

		echo "<pre>";
		echo $resultado;
		echo "</pre>";



	}

	function analisarLLM($codTarefaTurmaAluno, $prompt, $codigoAluno, $enunciado, $outputCorrecao) {
		$promptFinal = str_replace("{codigoAluno}", $codigoAluno, $prompt);
		$promptFinal = str_replace("{enunciado}", $enunciado, $promptFinal);
		$promptFinal = str_replace("{outputCorrecao}", $outputCorrecao, $promptFinal);
		#echo "<pre>";
		#print_r($promptFinal);
		#echo "</pre>";
		#return;

		$ollama_server = "http://143.107.137.61:11434";
		$ollama_model = "gemma4:e4b";
		$url        = "$ollama_server/api/generate";

		$data = [
			'model'  => $ollama_model, // Nome do modelo instalado no servidor remoto
			'prompt' => $promptFinal,
			'stream' => false    // Desativa o streaming para receber JSON único
		];

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => json_encode($data),
			CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
			CURLOPT_TIMEOUT        => 480 // Tempo limite em segundos
		]);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$resp = 'Erro cURL: ' . curl_error($ch);
		} else {
			$result = json_decode($response, true);
			#echo "<pre>";
			$resp = $result['response'] ?? 'Erro na resposta';
			#echo "</pre>";
		}
		curl_close($ch);
		return $resp;
	}




	function llm() {
		$db = $this->db;
		$codtarefa = $_REQUEST['codtarefa'];
		$codtarefaturma = $_REQUEST['cod'];

		echo "<h4>Análise de Código via LLM</h4>";

		echo "<br><a href='?modo=llm&amp;cod=$codtarefaturma&amp;codtarefa=$codtarefa&amp;processar=1' class='btn btn-warning'>Corrigir via  LLM</a>";


		if (! isset($_REQUEST['processar'])) {
			return;
		}



		$cmd = "SELECT t.* FROM tarefa t WHERE codtarefa = :codtarefa";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codtarefa', $codtarefa, PDO::PARAM_INT);
		$tbl->execute();
		$rowTbl = $tbl->fetch();
		$prompt = $rowTbl['promptanalisellm'];
		$enunciado = $rowTbl['instrucoes'];

		#echo "<pre>";
		#print_r($rowTbl);
		#echo "</pre>";


		# montando dicionario com alunos da turma
		$cmd = "SELECT " .
			"tta.codtarefaturmaaluno " .
			", a.nome " .
			", tt.codturma " .
			", t.codcurso " .
			", tta.nomearquivo " .
			", tta.resultados " .
			"FROM tarefaturmaaluno tta " .
			"INNER JOIN aluno a ON a.codaluno = tta.codaluno " .
			"INNER JOIN tarefaturma tt ON tt.codtarefaturma = tta.codtarefaturma " .
			"INNER JOIN turma t ON t.codturma = tt.codturma " .
			"WHERE tta.codtarefaturma = :codtarefaturma " .
			"AND tta.entregas > 0 " .
			"AND tta.notallm is null";
		$tblAlunos = $db->prepare($cmd);
		$tblAlunos->bindValue(':codtarefaturma', $codtarefaturma, PDO::PARAM_INT);
		$tblAlunos->execute();
		#$alunos = array();
		session_write_close();

		while ($rowAluno = $tblAlunos->fetch()) {
			#$alunos[$rowAluno['codtarefaturmaaluno']] = $rowAluno['nome'];
			#echo "<pre>";
			#print_r($rowAluno);
			#print_r($prompt);
			#echo "</pre>";
		




			$outputCorrecao = $rowAluno['resultados'];
			$codtarefaturmaaluno = $rowAluno['codtarefaturmaaluno'];
			$nomeAluno = $rowAluno['nome'];
			# codigo do aluno
			#echo "<tr><td colspan='6'><pre>";
			#echo "Diretório: TURMA$rowAluno[codturma]/TTURMA$codtarefaturma/TTALUNO$rowAluno[codtarefaturmaaluno]\n";
			$diretorio = "../uploads/CURSO$rowAluno[codcurso]/TURMA$rowAluno[codturma]/TTURMA$codtarefaturma/TTALUNO$rowAluno[codtarefaturmaaluno]";
			#echo "Diretório: $diretorio\n";


			#echo $rowAluno["resultados"];
			$outputCorrecao = $rowAluno["resultados"];
			$codigoAluno = "";
			
			#echo "</pre></td></tr>";

			
			


			if (is_dir($diretorio)) {
				// Lê todos os itens do diretório
				$itens = scandir($diretorio);

				foreach ($itens as $item) {
					// Ignora os ponteiros do sistema (. e ..)
					if ($item === '.' || $item === '..'|| $item === 'grader.sh') {
						continue;
					}

					if ('.txt' === substr($item, -4)) {
						continue;
					}

					if ('.norm' === substr($item, -5)) {
						continue;
					}

					$caminhoCompleto = $diretorio . DIRECTORY_SEPARATOR . $item;

					// Processa apenas se for um arquivo
					if (is_file($caminhoCompleto)) {

						// Obtém o tipo MIME do arquivo
						$tipoMime = mime_content_type($caminhoCompleto);

						// Verifica se o arquivo é de texto (ex: text/plain, text/html, etc.)
						if (strpos($tipoMime, 'text/') === 0) {
							#echo "Arquivo: $item\n";
							#echo "Conteúdo:\n";
							$conteudo = file_get_contents($caminhoCompleto);
							#if ($item == $rowAluno["nomearquivo"]) {
							#	$codigoAluno = $conteudo;
							#}
							$codigoAluno .= $conteudo;
						}
					}
				}
			} else {
				#echo "O diretório informado não existe.";
			}
			#echo $outputCorrecao;


			if ($codigoAluno == "") {
				echo "<pre>codigo do aluno não encontrado para o aluno $nomeAluno (codtarefaturmaaluno: $codtarefaturmaaluno)\n</pre>";
				continue;
			}


			$analiseLLM = $this->analisarLLM($codtarefaturmaaluno, $prompt, $codigoAluno, $enunciado, $outputCorrecao);
			#echo "<pre>Dados brutos:\n";
			#print_r($analiseLLM);
			#echo "</pre>";
		


			if (is_string($analiseLLM)) {
				// 1. Remove cercas de markdown (```json e ```) caso existam
				$jsonLimpo = preg_replace('/^```(?:json)?\s*|\s*```$/im', '', trim($analiseLLM));

				// 2. Garante a extração apenas do objeto JSON (da primeira '{' até a última '}')
				if (preg_match('/\{.*\}/s', $jsonLimpo, $matches)) {
					$jsonLimpo = $matches[0];
				}

				// 3. Sanitiza escapes e quebras de linha APENAS dentro das strings JSON
				$jsonLimpo = preg_replace_callback('/"(?:\\\\.|[^\\\\"])*"/s', function ($m) {
					// Primeiro, escapa quebras de linha e tabs reais dentro da string
					$str = str_replace(["\r\n", "\r", "\n", "\t"], ['\n', '\n', '\n', '\t'], $m[0]);
					
					// Depois, consome pares de escape válidos (ex: \\, \", \n, \u1234) intactos,
					// e duplica apenas barras soltas inválidas (ex: \le -> \\le)
					return preg_replace_callback('/\\\\(["\\\\\/bfnrt]|u[0-9a-fA-F]{4}|.)/s', function ($esc) {
						// Se o caractere após a barra for um escape JSON válido, mantém como veio
						if (preg_match('/^(?:["\\\\\/bfnrt]|u[0-9a-fA-F]{4})$/', $esc[1])) {
							return $esc[0];
						}
						// Caso contrário (ex: \l de \le), escapa a barra
						return '\\\\' . $esc[1];
					}, $str);
				}, $jsonLimpo);				
				
				$dadosLLM = json_decode($jsonLimpo, true);

				// Depuração caso ainda ocorra algum erro de sintaxe
				if (json_last_error() !== JSON_ERROR_NONE) {
					echo "Erro JSON: " . json_last_error_msg();
				}

				#echo "<pre>\ndados decodificados do LLM:\n";
				#print_r($dadosLLM);
				#echo "\n-----</pre>";
			} else {
				$dadosLLM = $analiseLLM;
			}


			echo "<pre>";
			#print_r($dadosLLM);
			echo "codtarefaturmaaluno: $codtarefaturmaaluno\t";
			

			// Decodifica o JSON retornado pela LLM (caso já não venha como array)
			#$dadosLLM = is_string($analiseLLM) ? json_decode($analiseLLM, true) : $analiseLLM;

			$report = $dadosLLM['report'] ?? $analiseLLM; // Se não houver 'report', mantém o texto original da análise
			$grade = $dadosLLM['grade'] ?? null;
			$aiProbability = $dadosLLM['AI_generated_probability'] ?? null;

			echo "grade: " . ($dadosLLM['grade'] ?? 'N/A') . "\t";
			echo "AI_generated_probability: " . ($dadosLLM['AI_generated_probability'] ?? 'N/A') . "\t";
			#echo "</pre>";


			// Atualiza os campos analisellm, notallm e geradoia na tabela tarefaturmaaluno
			$cmd = "UPDATE tarefaturmaaluno 
					SET analisellm = :analisellm, 
						notallm = :notallm, 
						geradoia = :geradoia 
					WHERE codtarefaturmaaluno = :codtarefaturmaaluno";

			$stmt = $db->prepare($cmd);
			$stmt->bindValue(':analisellm', $report, PDO::PARAM_STR);
			$stmt->bindValue(':notallm', $grade, $grade !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$stmt->bindValue(':geradoia', $aiProbability, $aiProbability !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$stmt->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
			$stmt->execute();

			echo "Aluno: $nomeAluno (ok)\n";
			echo "</pre>";



			#break;
		}
		#echo "<pre>";
		#print_r($alunos);
		#echo "</pre>";






	}


	function acao() {
		if ($this->modo == "salvar") {
			$this->salvar();
		}
		if ($this->modo == "salvarNotas") {
			$this->salvarNotas();
		}
		if ($this->modo == "processarNotas") {
			$this->processarNotas();
		}
		if ($this->modo == "exclui") {
			$this->excluir();
		}
		if ($this->modo == "excluirAluno") {
			$this->excluirAluno();
		}
		if ($this->modo == "upload") {
			$this->importarAlunos();
		}
		if ($this->modo == "moss") {
			$this->formulario($headerMode = true);
			$this->moss();
		}
		if ($this->modo == "llm") {
			$this->formulario($headerMode = true);
			$this->llm();
		} if ($this->modo == "novo" || $this->modo == "alterar") {
				$this->formulario();
		} else {
			$this->listar();
		}
	}
}

$frm = new Form($arquivo, $db);
?>
</div>
</body>
</html>