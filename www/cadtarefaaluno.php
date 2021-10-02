<?php require_once 'header.php';



class Form {
	
	private $db;
	private $modo;
	private $arquivo;
	
	
	function __construct($arquivo) {
		$this->modo = $_REQUEST["modo"];
		$this->arquivo = $arquivo;
		$this->db = new SQLite3('../db/pgrader.db');
		if (! $this->db)
			echo "não abriu bd";
		$this->acao();
	}
	
	
	function formulario() {
		$db = $this->db;
		
		$cmd = "SELECT t.* FROM tarefa t WHERE codtarefa = :codtarefa";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefa', $_REQUEST['codtarefa'], SQLITE3_INTEGER);
		$tbl = $stmt->execute();
		$rowTbl = $tbl->fetchArray();

		echo "<table class='table'>" .
				"<tr>" .
				"<th>Tarefa:</th>" .
				"<td>$rowTbl[descricao]</td>" .
				"<th>Sigla:</th>" .
				"<td>$rowTbl[sigla]</td>" .
				"<td><a href='cadtarefa,.php'>Voltar</a></td>" .
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
		<?php
	}
	
	function listar() {
		$db = $this->db;
		$cmd = "SELECT " .
				"tt.codturma , " .
				"tt.codtarefa , " .
				"tt.codtarefaturma , " .
				"STRFTIME('%d/%m/%Y', datainicio) as datainicio , " .
				"STRFTIME('%d/%m/%Y', datafim) as datafim , " .
				"tt.observacao , " .
				"t.codcurso , " .
				"t.descricao AS turma " .
				"FROM tarefaturma tt " .
				"INNER JOIN turma t ON t.codturma = tt.codturma " .
				"INNER JOIN tarefaturmaaluno tta ON tt.codtarefaturma = tta.codtarefaturma AND tta.codtarefaturmaaluno = :codtarefaturmaaluno " .
				"WHERE tt.codtarefa = :codtarefa ";
				#"ORDER BY nome asc";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':codtarefa', $_REQUEST['codtarefa'], SQLITE3_INTEGER);
		$stmt->bindValue(':codtarefaturmaaluno', $_REQUEST['cp'], SQLITE3_INTEGER);
		$tbl = $stmt->execute();

		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th>Cod</th>" .
				"<th>Turma</th>" .
				"<th>Início</th>" .
				"<th>Fim</th>" .
				"<th>Observações</th>" .
				"</tr>";


		while ($row = $tbl->fetchArray()) {
			echo "<tr>";
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
					<div id="alunos<?php echo $row['codtarefaturma']; ?>" class="card-body">
						<?php
						$cmd = "SELECT " .
								"tta.codtarefaturmaaluno , " .
								"a.nome , " .
								"STRFTIME('%d/%m/%Y', dataentrega) as dataentrega , " .
								"tta.entregas , " .
								"tta.resultados , " .
								"tta.nota " .
								"FROM tarefaturmaaluno tta " .
								"INNER JOIN aluno a ON tta.codaluno = a.codaluno " .
								"WHERE codtarefaturma =  :codtarefaturma " . 
								"and tta.codtarefaturmaaluno = :codtarefaturmaaluno " .
								"ORDER BY nome ASC";
						$stmt = $db->prepare($cmd);
						$stmt->bindValue(':codtarefaturma', $row['codtarefaturma'], SQLITE3_INTEGER);
						$stmt->bindValue(':codtarefaturmaaluno', $_REQUEST['cp'], SQLITE3_INTEGER);
						$tblAlunos = $stmt->execute();
						echo "<table class=\"table table-striped\" style=\"table-layout:fixed; word-wrap:break-word;\">" .
							"<tr>" .
							"<th>Cod</th>" .
							"<th>Aluno</th>" .
							"<th>Data</th>" .
							"<th>Entregas</th>" .
							"<th>Nota</th>" .
							"</tr>";

						while ($rowAluno = $tblAlunos->fetchArray()) {
							echo "<tr>" .
									"<td>$rowAluno[codtarefaturmaaluno]</td>" .
									"<td>$rowAluno[nome]</td>" .
									"<td>$rowAluno[dataentrega]</td>" .
									"<td>$rowAluno[entregas]</td>" .
									"<td>$rowAluno[nota]</td>" .
									"</tr>";
							if ($rowAluno["resultados"]) {
								echo "<tr><td colspan='5'><pre>";
								echo "Diretório: TURMA$row[codturma]/TTURMA$row[codtarefaturma]/TTALUNO$rowAluno[codtarefaturmaaluno]\n";
								$diretorio = "../uploads/CURSO$row[codcurso]/TURMA$row[codturma]/TTURMA$row[codtarefaturma]/TTALUNO$rowAluno[codtarefaturmaaluno]";


								echo $rowAluno["resultados"];
								
								echo "</pre></td></tr>";

								foreach(preg_split("/((\r?\n)|(\r\n?))/", $rowAluno["resultados"]) as $linha){
									// do stuff with $line
									if (strpos($linha, "  inflating:") !== FALSE && strpos($linha, ".exe") === FALSE) {
										echo "<tr><td colspan='5'><pre>";
										$arq = trim(substr($linha, 13));
										echo "$arq:<br><br>";
										

										#$arq = $diretorio 
										echo file_get_contents( "$diretorio/$arq");
										echo "</pre></td></tr>";
									}
								} 


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


		$this->formulario();
	
	
		$this->listar();
	}
}





#error_reporting(E_ALL);

$frm = new Form($arquivo);




?>

</div>
</body>
</html>