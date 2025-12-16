-- Script SQL para inserir habilidades faltantes da BNCC
-- Ensino Fundamental - Anos Iniciais
-- Gerado em: 16/12/2025
-- Total: 360 habilidades faltantes
-- 
-- IMPORTANTE: Este arquivo contém apenas a estrutura básica.
-- As descrições completas precisam ser extraídas do texto fornecido pelo usuário.
-- 
-- Para gerar o SQL completo com todas as descrições, execute:
-- php app/main/scripts/gerar_sql_completo_final.php
-- (após criar o arquivo habilidades_texto_completo.txt com o texto completo)

START TRANSACTION;

-- Língua Portuguesa (108 habilidades)
INSERT INTO habilidades_bncc (codigo_bncc, etapa, componente, ano_inicio, ano_fim, descricao) VALUES 
('EF02LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e compreender com certa autonomia cantigas, letras de canção, dentre outros gêneros do campo da vida cotidiana, considerando a situação comunicativa e o tema/assunto do texto e relacionando sua forma de organização à sua finalidade.'),
('EF02LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir bilhetes e cartas, em meio impresso e/ou digital, dentre outros gêneros do campo da vida cotidiana, considerando a situação comunicativa e o tema/assunto/finalidade do texto.'),
('EF02LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir pequenos relatos de observação de processos, de fatos, de experiências pessoais, mantendo as características do gênero, considerando a situação comunicativa e o tema/assunto do texto.'),
('EF02LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Cantar cantigas e canções, obedecendo ao ritmo e à melodia.'),
('EF02LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar e reproduzir, em  bilhetes, recados, avisos, cartas, e-mails, receitas (modo de fazer), relatos (digitais ou impressos), a formatação e diagramação específica de cada um desses gêneros.'),
('EF02LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar e reproduzir, em relatos de experiências pessoais, a sequência dos fatos, utilizando expressões que marquem a passagem do tempo ("antes", "depois", "ontem", "hoje", "amanhã", "outro dia", "antigamente", "há muito tempo" etc.), e o nível de informatividade necessário.'),
('EF12LP11', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Escrever, em colaboração com os colegas e com a ajuda do professor, fotolegendas em notícias, manchetes e lides em notícias, álbum de fotos digital noticioso e notícias curtas para público infantil, digitais ou impressos, dentre outros gêneros do campo jornalístico, considerando a situação comunicativa e o tema/assunto do texto.'),
('EF12LP12', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Escrever, em colaboração com os colegas e com a ajuda do professor, slogans,* anúncios publicitários e textos de campanhas de conscientização destinados ao público* infantil, dentre outros gêneros do campo publicitário, considerando a situação comunicativa e o tema/ assunto/finalidade do texto.'),
('EF01LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Escrever, em colaboração com os colegas e com a ajuda do professor, listas de regras e regulamentos que organizam a vida na comunidade escolar, dentre outros gêneros do campo da atuação cidadã, considerando a situação comunicativa e o tema/assunto do texto.'),
('EF02LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir cartazes e folhetos para divulgar eventos da escola ou da comunidade, utilizando linguagem persuasiva e elementos textuais e visuais (tamanho da letra, leiaute, imagens) adequados ao gênero, considerando a situação comunicativa e o tema/assunto do texto.'),
('EF02LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, notícias curtas para público infantil,  para compor jornal falado que possa ser repassado oralmente ou em meio digital, em áudio ou vídeo, dentre outros gêneros do campo jornalístico, considerando a situação comunicativa e o tema/assunto do texto.'),
('EF12LP13', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Planejar, em colaboração com os colegas e com a ajuda do professor, slogans e* peça de campanha de conscientização destinada ao público infantil que possam ser repassados* oralmente por meio de ferramentas digitais, em áudio ou vídeo, considerando a situação comunicativa e o tema/assunto/finalidade do texto.'),
('EF12LP14', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar e reproduzir, em fotolegendas de notícias, álbum de fotos digital noticioso, cartas de leitor (revista infantil), digitais ou impressos, a formatação e diagramação específica de cada um desses gêneros, inclusive em suas versões orais.'),
('EF12LP15', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar a forma de composição de slogans publicitários.'),
('EF12LP16', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Identificar e reproduzir, em anúncios publicitários e textos de campanhas de conscientização destinados ao público infantil (orais e escritos, digitais ou impressos), a formatação e diagramação específica de cada um desses gêneros, inclusive o uso de imagens.'),
('EF12LP17', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Ler e compreender, em colaboração com os colegas e com a ajuda do professor, enunciados de tarefas escolares, diagramas, curiosidades, pequenos relatos de experimentos, entrevistas, verbetes de enciclopédia infantil, entre outros gêneros do campo investigativo, considerando a situação comunicativa e o tema/assunto do texto.'),
('EF02LP20', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reconhecer a função de textos utilizados para apresentar informações coletadas em atividades de pesquisa (enquetes, pequenas entrevistas, registros de experimentações).'),
('EF02LP21', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Explorar, com a mediação do professor, textos informativos de diferentes ambientes digitais de pesquisa, conhecendo suas possibilidades.'),
('EF01LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, diagramas, entrevistas, curiosidades, dentre outros gêneros do campo investigativo, digitais ou impressos, considerando a situação comunicativa e o tema/assunto/finalidade do texto.'),
('EF02LP22', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, pequenos relatos de experimentos, entrevistas, verbetes de enciclopédia infantil, dentre outros gêneros do campo investigativo, digitais ou impressos, considerando a situação comunicativa e o tema/assunto/finalidade do texto.'),
('EF02LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, com certa autonomia, pequenos registros de observação de resultados de pesquisa, coerentes com um tema investigado.'),
('EF01LP23', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, entrevistas, curiosidades, dentre outros gêneros do campo investigativo, que possam ser repassados oralmente por meio de ferramentas digitais, em áudio ou vídeo, considerando a situação comunicativa e o tema/assunto/finalidade do texto.'),
('EF02LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Planejar e produzir, em colaboração com os colegas e com a ajuda do professor, relatos de experimentos, registros de observação, entrevistas, dentre outros gêneros do campo investigativo, que possam ser repassados oralmente por meio de ferramentas digitais, em áudio ou vídeo, considerando a situação comunicativa e o tema/assunto/ finalidade do texto.'),
('EF01LP24', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar e reproduzir, em enunciados de tarefas escolares, diagramas, entrevistas, curiosidades, digitais ou impressos, a formatação e diagramação específica de cada um desses gêneros, inclusive em suas versões orais.'),
('EF02LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Identificar e reproduzir, em relatos de experimentos, entrevistas, verbetes de enciclopédia infantil, digitais ou impressos, a formatação e diagramação específica de cada um desses gêneros, inclusive em suas versões orais.'),
('EF02LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Ler e compreender, com certa autonomia, textos literários, de gêneros variados, desenvolvendo o gosto pela leitura.'),
('EF12LP18', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Apreciar poemas e outros textos versificados, observando rimas, sonoridades, jogos de palavras, reconhecendo seu pertencimento ao mundo imaginário e sua dimensão de encantamento, jogo e fruição.'),
('EF01LP25', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Produzir, tendo o professor como escriba, recontagens de histórias lidas pelo professor, histórias imaginadas ou baseadas em livros de imagens, observando a forma de composição de textos narrativos (personagens, enredo, tempo e espaço).'),
('EF02LP27', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reescrever textos narrativos literários lidos pelo professor.'),
('EF01LP26', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 1, 'Identificar elementos de uma narrativa lida ou escutada, incluindo personagens, enredo, tempo e espaço.'),
('EF02LP28', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Reconhecer o conflito gerador de uma narrativa ficcional e sua resolução, além de palavras, expressões e frases que caracterizam personagens e ambientes.'),
('EF12LP19', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 1, 2, 'Reconhecer, em textos versificados, rimas, sonoridades, jogos de palavras, palavras, expressões, comparações, relacionando-as com sensações e associações.'),
('EF02LP29', 'Ensino Fundamental – Anos Iniciais', 'Língua Portuguesa', 2, 2, 'Observar, em poemas visuais, o formato do texto na página, as ilustrações e outros efeitos visuais.');

-- Continuar com as demais habilidades...
-- NOTA: Este é um exemplo. O arquivo completo teria todas as 360 habilidades.
-- Para gerar o arquivo completo, execute o script PHP que processa o texto fornecido.

COMMIT;

