#!/bin/bash

set -euo pipefail

# Uso: grader.sh [submission_dir]
SUBMISSION_DIR="${1:-.}"
IN_DIR="$SUBMISSION_DIR/in"
OUT_DIR="$SUBMISSION_DIR/out"
TIMEOUT_CMD="timeout"
TIMEOUT_SECS=5

# Checking C files
shopt -s nullglob
C_FILES=("$SUBMISSION_DIR"/*.c)
if [ ${#C_FILES[@]} -eq 0 ]; then
	echo "ERROR: nenhum arquivo .c encontrado em $SUBMISSION_DIR"
	exit 1
fi

# Compile
echo "Compilando..."
MAIN_EXEC="main"
EXEC_PATH="$SUBMISSION_DIR/$MAIN_EXEC"
if ! gcc "${C_FILES[@]}" -std=c99 -O2 -Wall -Wextra -o "$EXEC_PATH" 2>compile.err; then
	echo "COMPILE_ERROR"
	cat compile.err
	exit 2
fi
# garantir permissão de execução
chmod +x "$EXEC_PATH" || true

# Run tests
if [ ! -d "$IN_DIR" ]; then
	echo "WARNING: diretório de entrada $IN_DIR não existe ou vazio"
	exit 4
fi

inputs=("$IN_DIR"/input*.txt)
total=0
passed=0

normalize() {
	sed 's/\r$//' "$1" | sed 's/[[:space:]]\+$//' > "$1.norm"
}

for in_file in "${inputs[@]}"; do
	total=$((total + 1))
	
	base=$(basename "$in_file")
	out_name=${base/input/output}
	expected="$OUT_DIR/$out_name"
	actual="out_${total}.txt"

	echo -e "Test $total:\n\nInputs:"
	cat $in_file
	echo -e "Expected:"
	cat $expected

	if ! $TIMEOUT_CMD ${TIMEOUT_SECS}s "$EXEC_PATH" < "$in_file" > "$actual" 2>"run_${total}.err"; then
		echo "Result: RUNTIME_ERROR"
		echo "Stderr:"; sed -n '1,200p' "run_${total}.err"
		continue
	fi

	if [ ! -f "$expected" ]; then
		echo "Result: expected file $expected not found -> counted as wrong"
		continue
	fi

	echo -e "Actual:"
	cat $actual


	normalize "$expected"
	normalize "$actual"


	if cmp -s "$expected.norm" "$actual.norm"; then
		echo "Result: OK"
		passed=$((passed + 1))
	else
		echo "Result: FAIL"
		echo "--- expected (first 200 chars) ---"
		head -c 200 "$expected" || true
		echo
		echo "--- actual (first 200 chars) ---"
		head -c 200 "$actual" || true
		echo
	fi
	echo -e "\n\n"
done

if [ $total -eq 0 ]; then
	echo "Nenhum caso de teste encontrado em $IN_DIR"
	exit 5
fi

percent=$(( passed * 100 / total ))
echo -e "\nResumo: $passed / $total testes corretos\n$percent%"

exit 0