#!/bin/bash

echo "=== TESTE DE VALIDAÇÃO - EXECUTANDO 10 VEZES ==="
echo "Data: $(date)"
echo ""

TOTAL_EXECUCOES=10
SUCESSOS=0
FALHAS=0

# Arquivo para logs detalhados
LOG_FILE="teste_validacao_$(date +%Y%m%d_%H%M%S).log"

for i in $(seq 1 $TOTAL_EXECUCOES); do
    echo "Execução $i de $TOTAL_EXECUCOES..."
    echo "=== EXECUÇÃO $i ===" >> "$LOG_FILE"
    
    # Executa o programa e captura a saída
    OUTPUT=$(php restaurante_semaforo.php 2>&1)
    echo "$OUTPUT" >> "$LOG_FILE"
    
    # Verifica se todos os critérios foram atendidos
    TODOS_PROCESSADOS=$(echo "$OUTPUT" | grep -c "✓ Todos os pedidos foram processados")
    SEM_DUPLICATAS=$(echo "$OUTPUT" | grep -c "✓ Nenhum pedido foi duplicado")
    SEM_PERDIDOS=$(echo "$OUTPUT" | grep -c "✓ Nenhum pedido foi perdido")
    FILA_VAZIA=$(echo "$OUTPUT" | grep -c "✓ A fila de pedidos terminou vazia")
    SOMA_CORRETA=$(echo "$OUTPUT" | grep -c "✓ Soma matemática correta")
    
    # Conta total de validações bem-sucedidas
    VALIDACOES_OK=$((TODOS_PROCESSADOS + SEM_DUPLICATAS + SEM_PERDIDOS + FILA_VAZIA + SOMA_CORRETA))
    
    if [ $VALIDACOES_OK -eq 5 ]; then
        echo "  ✓ Sucesso - Todas as validações passaram"
        SUCESSOS=$((SUCESSOS + 1))
    else
        echo "  ✗ Falha - Validações que passaram: $VALIDACOES_OK/5"
        FALHAS=$((FALHAS + 1))
        
        # Mostra quais validações falharam
        if [ $TODOS_PROCESSADOS -eq 0 ]; then
            echo "    - Nem todos os pedidos foram processados"
        fi
        if [ $SEM_DUPLICATAS -eq 0 ]; then
            echo "    - Foram encontrados pedidos duplicados"
        fi
        if [ $SEM_PERDIDOS -eq 0 ]; then
            echo "    - Alguns pedidos foram perdidos"
        fi
        if [ $FILA_VAZIA -eq 0 ]; then
            echo "    - A fila não terminou vazia"
        fi
        if [ $SOMA_CORRETA -eq 0 ]; then
            echo "    - Soma matemática incorreta"
        fi
    fi
    
    echo "" >> "$LOG_FILE"
    sleep 1  # Pequena pausa entre execuções
done

echo ""
echo "=== RESUMO DOS TESTES ==="
echo "Total de execuções: $TOTAL_EXECUCOES"
echo "Sucessos: $SUCESSOS"
echo "Falhas: $FALHAS"
echo "Taxa de sucesso: $(( (SUCESSOS * 100) / TOTAL_EXECUCOES ))%"
echo ""
echo "Log detalhado salvo em: $LOG_FILE"

if [ $FALHAS -eq 0 ]; then
    echo "🎉 RESULTADO: Todos os testes passaram! O sistema funciona corretamente."
    exit 0
else
    echo "⚠️  RESULTADO: Alguns testes falharam. Verificar log para detalhes."
    exit 1
fi
