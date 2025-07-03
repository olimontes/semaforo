<?php

echo "=== TESTE DA VERSÃO COM ERROS - DEMONSTRAÇÃO DE RACE CONDITIONS ===\n";
echo "Este script executa múltiplas vezes o código SEM semáforo para mostrar os problemas\n\n";

$total_testes = 5; // Menos testes pois vamos demonstrar os erros
$problemas_encontrados = 0;

for ($i = 1; $i <= $total_testes; $i++) {
    echo "--- TESTE $i de $total_testes ---\n";
    
    // Executa o programa com erros
    $output = shell_exec('php restaurante_com_erros.php 2>&1');
    
    if ($output === null) {
        echo "❌ Erro ao executar o programa\n";
        continue;
    }
    
    // Analisa a saída procurando por erros
    $tem_erros = false;
    
    if (strpos($output, '❌ ERRO:') !== false) {
        $tem_erros = true;
        $problemas_encontrados++;
        
        echo "🔴 PROBLEMAS DETECTADOS neste teste:\n";
        
        // Extrai linhas com erros
        $linhas = explode("\n", $output);
        foreach ($linhas as $linha) {
            if (strpos($linha, '❌ ERRO:') !== false) {
                echo "   " . trim($linha) . "\n";
            }
            if (strpos($linha, '   →') !== false) {
                echo "   " . trim($linha) . "\n";
            }
        }
    } else {
        echo "🟢 Nenhum erro detectado neste teste (sorte!)\n";
    }
    
    echo "\n";
    
    // Salva log detalhado
    file_put_contents("teste_erros_detalhado.log", 
        "=== TESTE COM ERROS $i ===\n" . $output . "\n\n", 
        FILE_APPEND | LOCK_EX);
        
    sleep(1); // Pausa entre testes
}

echo "=== RESUMO DOS TESTES COM ERROS ===\n";
echo "Total de testes: $total_testes\n";
echo "Testes com problemas: $problemas_encontrados\n";
echo "Testes sem problemas: " . ($total_testes - $problemas_encontrados) . "\n";
echo "Taxa de problemas: " . round(($problemas_encontrados * 100) / $total_testes, 1) . "%\n\n";

if ($problemas_encontrados > 0) {
    echo "⚠️  DEMONSTRAÇÃO BEM-SUCEDIDA!\n";
    echo "Os erros acima provam que SEM semáforo o sistema falha devido a race conditions.\n";
    echo "Compare com a versão que usa semáforo, que tem 100% de taxa de sucesso.\n";
} else {
    echo "🤔 Hmm, não encontramos erros desta vez...\n";
    echo "As race conditions são intermitentes - execute novamente para ver os problemas.\n";
    echo "Em sistemas com alta concorrência, estes erros seriam mais frequentes.\n";
}

echo "\nLog detalhado salvo em: teste_erros_detalhado.log\n";

?>
