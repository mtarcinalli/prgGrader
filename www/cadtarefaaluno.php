<?php
require_once 'header.php';
require_once 'Parsedown.php';

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
	
	function formulario() {
		$db = $this->db;
		$cmd = "SELECT t.* FROM tarefa t WHERE codtarefa = :codtarefa";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codtarefa', $_REQUEST['codtarefa'], PDO::PARAM_INT);
		$tbl->execute();
		$rowTbl = $tbl->fetch();
		$prompt = $rowTbl['promptanalisellm'];

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
		$enunciado = $rowTbl['instrucoes'];
		?>
		<hr>
		<?php
		$cmd = "SELECT " .
				"tt.codturma , " .
				"tt.codtarefa , " .
				"tt.codtarefaturma , " .
				"to_char(datainicio, 'DD/MM/YY') as datainicio , " .
				"to_char(datafim, 'DD/MM/YY') as datafim , " .
				"tt.observacao , " .
				"t.codcurso , " .
				"t.descricao AS turma " .
				"FROM tarefaturma tt " .
				"INNER JOIN turma t ON t.codturma = tt.codturma " .
				"INNER JOIN tarefaturmaaluno tta ON tt.codtarefaturma = tta.codtarefaturma AND tta.codtarefaturmaaluno = :codtarefaturmaaluno " .
				"WHERE tt.codtarefa = :codtarefa ";
				#"ORDER BY nome asc";
		$tbl = $db->prepare($cmd);
		$tbl->bindValue(':codtarefa', $_REQUEST['codtarefa'], PDO::PARAM_INT);
		$tbl->bindValue(':codtarefaturmaaluno', $_REQUEST['cp'], PDO::PARAM_INT);
		$tbl->execute();

		echo '<form action="cadtarefaaluno.php" method="post">';
		echo "<table class=\"table table-striped\">" .
				"<tr>" .
				"<th>Cod</th>" .
				"<th>Turma</th>" .
				"<th>Início</th>" .
				"<th>Fim</th>" .
				"<th>Observações</th>" .
				"</tr>";


		while ($row = $tbl->fetch()) {
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
						$codtarefaturmaaluno = $_REQUEST['cp'];
						$cmd = "SELECT " .
								"tta.codtarefaturmaaluno , " .
								"a.nome , " .
								"to_char(dataentrega, 'DD/MM/YYYY') as dataentrega , " .
								"tta.entregas , " .
								"tta.resultados , " .
								"tta.nota , " .
								"tta.notafinal ," .
								"tta.notallm , " .
								"tta.geradoia , " .
								"tta.plagiomoss , " .
								"tta.nomearquivo , " .
								"tta.analisellm , " .
								"tta.observacao " .
								"FROM tarefaturmaaluno tta " .
								"INNER JOIN aluno a ON tta.codaluno = a.codaluno " .
								"WHERE codtarefaturma =  :codtarefaturma " . 
								"and tta.codtarefaturmaaluno = :codtarefaturmaaluno " .
								"ORDER BY nome ASC";
						$tblAlunos = $db->prepare($cmd);
						$tblAlunos->bindValue(':codtarefaturma', $row['codtarefaturma'], PDO::PARAM_INT);
						$tblAlunos->bindValue(':codtarefaturmaaluno', $_REQUEST['cp'], PDO::PARAM_INT);
						$tblAlunos->execute();
						echo "<table class=\"table table-striped\" style=\"table-layout:fixed; word-wrap:break-word;\">" .
							"<tr>" .
							"<th>Cod</th>" .
							"<th>Aluno</th>" .
							"<th>Data</th>" .
							"<th>Entregas</th>" .
							"<th>Nota</th>" .
							"<th>Nota LLM</th>" .
							"<th>MOSS</th>" .
							"<th>IA</th>" .
							"<th>Nota Final</th>" .
							"</tr>";

						while ($rowAluno = $tblAlunos->fetch()) {
							echo "<tr>" .
									"<td>$rowAluno[codtarefaturmaaluno]</td>" .
									"<td>$rowAluno[nome]</td>" .
									"<td>$rowAluno[dataentrega]</td>" .
									"<td>$rowAluno[entregas]</td>" .
									"<td>$rowAluno[nota]</td>" .
									"<td>$rowAluno[notallm]</td>" .
									"<td>$rowAluno[plagiomoss]%</td>" .
									"<td>$rowAluno[geradoia]%</td>" .
									"<td><input type=\"text\" id=\"notafinal\" name=\"notafinal\" value=\"$rowAluno[notafinal]\"  class=\"form-control\"></td>" .
									"</tr>";
							echo "<tr>" .
									"<td>Observações:</td>" .
									"<td colspan='7'><textarea id=\"observacao\" name=\"observacao\" class=\"form-control\">$rowAluno[observacao]</textarea></td>" .
									"<td>" .
									"<input type=\"hidden\" name=\"cp\" value=\"$_REQUEST[cp]\">" .
									"<input type=\"hidden\" name=\"codtarefa\" value=\"$_REQUEST[codtarefa]\">" .
									"<input type=\"hidden\" name=\"modo\" value=\"salvar\">" .
									"<button type=\"submit\" class=\"btn btn-primary\">Salvar</button></td>" .
									"</tr>";

							$analiseLLM = $rowAluno["analisellm"];
							if ($rowAluno["resultados"]) {
								echo "<tr><td colspan='9'><pre>";
								echo "Diretório: TURMA$row[codturma]/TTURMA$row[codtarefaturma]/TTALUNO$rowAluno[codtarefaturmaaluno]\n";
								$diretorio = "../uploads/CURSO$row[codcurso]/TURMA$row[codturma]/TTURMA$row[codtarefaturma]/TTALUNO$rowAluno[codtarefaturmaaluno]";


								echo $rowAluno["resultados"];
								$outputCorrecao = $rowAluno["resultados"];
								
								
								echo "</pre></td></tr>";

								foreach(preg_split("/((\r?\n)|(\r\n?))/", $rowAluno["resultados"]) as $linha){
									if ((strpos($linha, "  inflating:") !== FALSE && strpos($linha, ".exe") === FALSE) || ($linha == "solution.h ")) {
										echo "\n<tr><td colspan='9'><pre>";
										$arq = trim(substr($linha, 13));
										echo "$arq:<br><br>";
										$output = file_get_contents("$diretorio/$arq");
										$enc = mb_detect_encoding($output);

										if ($output) {
											echo "ok\n$enc\n";
											$contents = htmlentities($output, ENT_QUOTES, $enc);
										} else {
											echo "erro: $diretorio/$arq\n\n$output";
										}
										if ($contents)
											echo "ok2: \n$contents</pre></td></tr>\n";
										else
											echo "not ok2:\n $output</pre></td></tr>\n";
									}
								} 
								
								


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
												echo "\n<tr><td colspan='9'><pre>";
												echo "Arquivo: $item\n";
												echo "Conteúdo:\n";
												$conteudo = file_get_contents($caminhoCompleto);
												echo htmlspecialchars($conteudo) . "\n";
												echo "</pre></td></tr>\n";
												if ($item == $rowAluno["nomearquivo"]) {
													$codigoAluno = $conteudo;
												}
											}
										}
									}
								} else {
									echo "O diretório informado não existe.";
								}
							}
						}
						
						


						echo "\n<tr><td colspan='9'>";
						echo "<h2>Analise LLM:</h2>\n";
						# inserir botao para executar a analise via llm e atualizar o campo analisellm na tabela tarefaturmaaluno
						#echo "<form action=\"cadtarefaaluno.php\" method=\"post\">";
						#echo "<input type=\"hidden\" name=\"cp\" value=\"$_REQUEST[cp]\">";
						#echo "<input type=\"hidden\" name=\"codtarefa\" value=\"$_REQUEST[codtarefa]\">";
						#echo "<input type=\"hidden\" name=\"modo\" value=\"analiseLLM\">";
						#echo "<input type=\"hidden\" name=\"analisellm\" value=\"1\">";
						#echo "<button type=\"submit\" class=\"btn btn-primary\">Executar Analise LLM</button>";
						#echo "</form>";
						
						if ($this->modo == "analiseLLM") {
							$analiseLLM = $this->analisarLLM($codtarefaturmaaluno, $prompt, $codigoAluno, $enunciado, $outputCorrecao);
							echo "<h3>Resultado da Análise LLM:</h3>";
							echo "<pre>";
							print_r($analiseLLM);
							echo "</pre>";


							if (is_string($analiseLLM)) {
								// 1. Remove cercas de markdown (```json e ```) caso existam
								$jsonLimpo = preg_replace('/^```(?:json)?\s*|\s*```$/im', '', trim($analiseLLM));

								// 2. Garante a extração apenas do objeto JSON (da primeira '{' até a última '}')
								if (preg_match('/\{.*\}/s', $jsonLimpo, $matches)) {
									$jsonLimpo = $matches[0];
								}

								$dadosLLM = json_decode($jsonLimpo, true);

								// Fallback: se o JSON vier quebrado, salva o texto bruto em $report para não perder a análise
								if (json_last_error() !== JSON_ERROR_NONE || !is_array($dadosLLM)) {
									$dadosLLM = [
										'report' => $analiseLLM,
										'grade' => null,
										'AI_generated_probability' => null
									];
								}
							} else {
								$dadosLLM = $analiseLLM;
							}
							echo "<pre>";
							print_r($dadosLLM);




							// Decodifica o JSON retornado pela LLM (caso já não venha como array)
							#$dadosLLM = is_string($analiseLLM) ? json_decode($analiseLLM, true) : $analiseLLM;

							$report = $dadosLLM['report'] ?? $analiseLLM; // Se não houver 'report', mantém o texto original da análise
							$grade = $dadosLLM['grade'] ?? null;
							$aiProbability = $dadosLLM['AI_generated_probability'] ?? null;

							echo "grade: " . ($dadosLLM['grade'] ?? 'N/A') . "\n";
							echo "AI_generated_probability: " . ($dadosLLM['AI_generated_probability'] ?? 'N/A') . "\n";
							echo "</pre>";


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


							// atualizar o campo analisellm na tabela tarefaturmaaluno
							#$cmd = "UPDATE tarefaturmaaluno SET analisellm = :analisellm where codtarefaturmaaluno = :codtarefaturmaaluno";
							#$stmt = $db->prepare($cmd);
							#$stmt->bindValue(':analisellm', $analiseLLM, PDO::PARAM_STR);
							#$stmt->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
							#$stmt->execute();
							$analiseLLM = $report; // Atualiza a variável para exibir o relatório atualizado
						}
						#$analiseLLM = $this->analisarLLM($codtarefaturmaaluno, $prompt, $codigoAluno, $enunciado, $outputCorrecao);
						
						$parsedown = new Parsedown();
						$html = $parsedown->text($analiseLLM);

							// 5. Exibe na tela
						echo $html;
						
						#echo htmlspecialchars($analiseLLM) . "\n";
						echo "</td></tr>\n";
						echo "</table>";
						?>
					</div>
				</td>
			<?php
		}
		echo "</table>";
		echo "</form>";
	}

	function analisarLLM($codTarefaTurmaAluno, $prompt, $codigoAluno, $enunciado, $outputCorrecao) {
		$promptFinal = str_replace("{codigoAluno}", $codigoAluno, $prompt);
		$promptFinal = str_replace("{enunciado}", $enunciado, $promptFinal);
		$promptFinal = str_replace("{outputCorrecao}", $outputCorrecao, $promptFinal);


		$ollama_server = "http://143.107.137.61:11434";
		$ollama_model = "gemma4:e4b";
		#$ollama_command = "ollama generate --model $ollama_model --prompt \"$prompt\"";
		#$ollama_output = shell_exec($ollama_command);

		#echo "Prompt: $promptFinal\n\n";


		#$remoteIp   = '192.168.1.100'; // IP ou hostname do servidor remoto
		#$port       = '11434';
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


	// $db = $this->db;
	// 	$cmd = "UPDATE tarefaturmaaluno SET analisellm = :analisellm where codtarefaturmaaluno = :codtarefaturmaaluno";
	// 	$stmt = $db->prepare($cmd);
	// 	$prompt = str_replace("{codigoAluno}", $codigoAluno, $prompt);
	// 	$prompt = str_replace("{enunciado}", $enunciado, $prompt);
	// 	$prompt = str_replace("{outputCorrecao}", $outputCorrecao, $prompt);
	// 	$stmt->bindValue(':analisellm', $prompt, PDO::PARAM_STR);
	// 	$stmt->bindValue(':codtarefaturmaaluno', $codtarefaturmaaluno, PDO::PARAM_INT);
	// 	$stmt->execute();
	// }
		
	function salvar() {
		$db = $this->db;
		$ok = true;
		$cmd = "UPDATE tarefaturmaaluno SET notafinal = :notafinal, observacao = :observacao where codtarefaturmaaluno = :codtarefaturmaaluno";
		$stmt = $db->prepare($cmd);
		$stmt->bindValue(':notafinal', $_REQUEST['notafinal'], PDO::PARAM_INT);
		$stmt->bindValue(':codtarefaturmaaluno', $_REQUEST['cp'], PDO::PARAM_INT);
		$stmt->bindValue(':observacao', $_REQUEST['observacao'], PDO::PARAM_STR);
		$ok = $stmt->execute();
		if ($ok) {
			echo "<div class=\"alert alert-success\" role=\"alert\">Notas finais salvas com sucesso!</div>";
		} else {
			echo "<div class=\"alert alert-danger\" role=\"alert\">Erro salvar notas finais!</div>";
		}
	}
 	
	
	
	
	function acao() {
		if ($this->modo == "salvar") {
			$this->salvar();
		}
		$this->formulario();
	}
}

#error_reporting(E_ALL);
$frm = new Form($arquivo, $db);

?>
</div>
</body>
</html>