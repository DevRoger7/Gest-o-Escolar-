<?php
/**
 * Script para processar a lista de habilidades da BNCC fornecida pelo usuário
 * e comparar com o banco de dados
 */

// Texto fornecido pelo usuário (primeira parte)
$textoHabilidades = "Ensino Fundamental – Anos Iniciais EF15LP01 - Identificar a função social de textos que circulam em campos da vida social dos quais participa cotidianamente (a casa, a rua, a comunidade, a escola) e nas mídias impressa, de massa e digital, reconhecendo para que foram produzidos, onde circulam, quem os produziu e a quem se destinam., EF15LP02 - Estabelecer expectativas em relação ao texto que vai ler (pressuposições antecipadoras dos sentidos, da forma e da função social do texto), apoiando-se em seus conhecimentos prévios sobre as condições de produção e recepção desse texto, o gênero, o suporte e o universo temático, bem como sobre saliências textuais, recursos gráficos, imagens, dados da própria obra (índice, prefácio etc.), confirmando antecipações e inferências realizadas antes e durante a leitura de textos, checando a adequação das hipóteses realizadas., EF15LP03 - Localizar informações explícitas em textos., EF15LP04 - Identificar o efeito de sentido produzido pelo uso de recursos expressivos  gráfico-visuais em textos multissemióticos., EF15LP05 - Planejar, com a ajuda do professor, o texto que será produzido, considerando a situação comunicativa, os interlocutores (quem escreve/para quem escreve); a finalidade ou o propósito (escrever para quê); a circulação (onde o texto vai circular); o suporte (qual é o portador do texto); a linguagem, organização e forma do texto e seu tema, pesquisando em meios impressos ou digitais, sempre que for preciso, informações necessárias à produção do texto, organizando em tópicos os dados e as fontes pesquisadas., EF15LP06 - Reler e revisar o texto produzido com a ajuda do professor e a colaboração dos colegas, para corrigi-lo e aprimorá-lo, fazendo cortes, acréscimos, reformulações, correções de ortografia e pontuação., EF15LP07 - Editar a versão final do texto, em colaboração com os colegas e com a ajuda do professor, ilustrando, quando for o caso, em suporte adequado, manual ou digital., EF15LP08 - Utilizar software, inclusive programas de edição de texto, para editar e publicar os* textos produzidos, explorando os recursos multissemióticos disponíveis., EF15LP09 - Expressar-se em situações de intercâmbio oral com clareza, preocupando-se em ser compreendido pelo interlocutor e usando a palavra com tom de voz audível, boa articulação e ritmo adequado., EF15LP10 - Escutar, com atenção, falas de professores e colegas, formulando perguntas pertinentes ao tema e solicitando esclarecimentos sempre que necessário., EF15LP11 - Reconhecer características da conversação espontânea presencial, respeitando os turnos de fala, selecionando e utilizando, durante a conversação, formas de tratamento adequadas, de acordo com a situação e a posição do interlocutor., EF15LP12 - Atribuir significado a aspectos não linguísticos (paralinguísticos) observados na fala, como direção do olhar, riso, gestos, movimentos da cabeça (de concordância ou discordância), expressão corporal, tom de voz., EF15LP13 - Identificar finalidades da interação oral em diferentes contextos comunicativos (solicitar informações, apresentar opiniões, informar, relatar experiências etc.)., EF15LP14 - Construir o sentido de histórias em quadrinhos e tirinhas, relacionando imagens e palavras e interpretando recursos gráficos (tipos de balões, de letras, onomatopeias)., EF15LP15 - Reconhecer que os textos literários fazem parte do mundo do imaginário e apresentam uma dimensão lúdica, de encantamento, valorizando-os, em sua diversidade cultural, como patrimônio artístico da humanidade., EF15LP16 - Ler e compreender, em colaboração com os colegas e com a ajuda do professor e, mais tarde, de maneira autônoma, textos narrativos de maior porte como contos (populares, de fadas, acumulativos, de assombração etc.) e crônicas., EF15LP17 - Apreciar poemas visuais e concretos, observando efeitos de sentido criados pelo formato do texto na página, distribuição e diagramação das letras, pelas ilustrações e por outros efeitos visuais., EF15LP18 - Relacionar texto com ilustrações e outros recursos gráficos., EF15LP19 - Recontar oralmente, com e sem apoio de imagem, textos literários lidos pelo professor.";

// Processar o texto para extrair códigos e descrições
function processarHabilidades($texto) {
    $habilidades = [];
    
    // Padrão para encontrar códigos BNCC: EF seguido de números e letras
    preg_match_all('/(EF\d{2}[A-Z]{2}\d{2})\s*-\s*([^,]+(?:\.,|$))/', $texto, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $codigo = trim($match[1]);
        $descricao = trim($match[2]);
        // Remover vírgula final se existir
        $descricao = rtrim($descricao, ',');
        
        $habilidades[$codigo] = $descricao;
    }
    
    return $habilidades;
}

// Processar habilidades do texto fornecido
$habilidadesProcessadas = processarHabilidades($textoHabilidades);

echo "Habilidades processadas: " . count($habilidadesProcessadas) . "\n";
echo "Primeiras 5 habilidades:\n";
$count = 0;
foreach ($habilidadesProcessadas as $codigo => $descricao) {
    if ($count++ < 5) {
        echo "$codigo: " . substr($descricao, 0, 80) . "...\n";
    }
}

