<?php
if ($api == 'listaEscalas') {
    if ($metodo == 'GET') {
        if ($acao == 'index' && $parametro == '') {

            // 1 - PEGAR TODAS AS LISTAS DE ESCALAS E ADICIONAR AS PROPRIEDADES escala, operadoresForaEscala e equipamentosForaEscala como arrays vazios;
            // 2 - FAZER UM LAÇO DE REPETIÇÃO E A CADA LISTA, PEGAR TODOS AS ESCALAS CORRESPONDENTES E ADICIONAR AO ARRAU

            $json = file_get_contents("php://input");
            $dados = json_decode($json, true);

            if (!$dados) {
                exit;
            }

            if (!array_key_exists('turma', $dados)) {
                $response = array(
                    "message" => 'Parâmetro \'turma\' não encontrado.'
                );
                echo json_encode($response);
                exit;
            }

            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM listaescalas WHERE listaescalas.turma = ?");
            $sql->execute([$dados['turma']]);
            $obj = $sql->fetchAll(PDO::FETCH_ASSOC);



            for ($i = 0; $i < count($obj); $i++) {
                $obj[$i]['escala'] = [];
                $obj[$i]['operadoresForaEscala'] = [];

                $sql = $db->prepare("SELECT operadores.matricula, operadores.nome, tag, operadorequipamento.localizacao, operadorequipamento.atividade, operadorequipamento.transporte FROM operadorequipamento, operadores where operadores.matricula = operadorequipamento.matricula and operadorequipamento.idlista = ?");
                $sql->execute([$obj[$i]['idLista']]);
                $escala = $sql->fetchAll(PDO::FETCH_ASSOC);

                for ($j = 0; $j < count($escala); $j++) {
                    $obj[$i]['escala'][] = $escala[$j];
                }

                $sql = $db->prepare("SELECT operadores.matricula, operadores.nome FROM operadorforaescala, operadores where operadores.matricula = operadorforaescala.matricula and  operadorforaescala.idlista = ?");
                $sql->execute([$obj[$i]['idLista']]);
                $operadorForaEscala = $sql->fetchAll(PDO::FETCH_ASSOC);

                for ($j = 0; $j < count($operadorForaEscala); $j++) {
                    $obj[$i]['operadoresForaEscala'][] = $operadorForaEscala[$j];
                }
            }

            if ($obj) {
                echo json_encode($obj);
            } else {
                $response = array(
                    "message" => "Nenhuma lista disponível."
                );
                echo json_encode($response);
            }
            exit;
        }

        if ($acao == 'show' && $parametro != '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM listaescalas WHERE listaescalas.idlista = ?");
            $sql->execute([$parametro]);
            $obj = $sql->fetch(PDO::FETCH_ASSOC);


            if (!$obj) {
                $response = array(
                    "message" => "Nenhuma lista disponível."
                );
                echo json_encode($response);
                exit;
            }

            $obj['escala'] = [];
            $obj['operadoresForaEscala'] = [];

            $sql = $db->prepare("SELECT operadores.matricula, operadores.nome, tag, operadorequipamento.localizacao, operadorequipamento.atividade, operadorequipamento.transporte FROM operadorequipamento, operadores where operadores.matricula = operadorequipamento.matricula and operadorequipamento.idlista = ?");
            $sql->execute([$parametro]);
            $escala = $sql->fetchAll(PDO::FETCH_ASSOC);

            for ($j = 0; $j < count($escala); $j++) {
                $obj['escala'][] = $escala[$j];
            }

            $sql = $db->prepare("SELECT operadores.matricula, operadores.nome FROM operadorforaescala, operadores where operadores.matricula = operadorforaescala.matricula and  operadorforaescala.idlista = ?");
            $sql->execute([$parametro]);
            $operadorForaEscala = $sql->fetchAll(PDO::FETCH_ASSOC);

            for ($j = 0; $j < count($operadorForaEscala); $j++) {
                $obj['operadoresForaEscala'][] = $operadorForaEscala[$j];
            }

            echo json_encode($obj);

            exit;
        }
    }

    if ($metodo == 'PUT') {
        if ($acao == 'update') {
            if ($parametro != "") {

                // 1 - VERIFICAR SE O OPERADOR A ADICIONAR EXISTE NA LISTA DE OPERADORES FORA DE ESCALA
                // 2 - SE EXISTE, REMOVER O OPERADOR DA LISTA DE FORA DE ESCALA
                // 3 - SE O OPERADOR ESCALADO FOR VÁLIDO, MANDA-LO PARA A LISTA FORA DE ESCALA
                // 4 - MODIFICAR A ESCALA, ADICIONANDO O OPERADOR

                // MÉTODO RECEBE UM JSON NO SEGUINTE FORMATO:
                // {
                //     "escala" : [
                //         {
                //             "matricula": MATRICULA,
                //             "tag": TAG,
                //             "localizacao": LOCALIZAÇÃO
                //             "atividade": ATIVIDADE
                //             "transporte": TRANSPORTE   
                //         },
                //     ]
                // }

                $json = file_get_contents("php://input");
                $dados = json_decode($json, true);

                if (!$dados) {
                    var_dump($dados);
                    exit;
                }

                if (!array_key_exists('escala', $dados)) {
                    $response = array(
                        "message" => 'Parâmetro \'escala\' não encontrado.'
                    );
                    echo json_encode($response);
                    exit;
                }

                foreach ($dados['escala'] as $escala) {
                    if (!array_key_exists('matricula', $escala) || !array_key_exists('tag', $escala) || !array_key_exists('localizacao', $escala) || !array_key_exists('atividade', $escala) || !array_key_exists('transporte', $escala)) {
                        echo json_encode([
                            "message" => "Parâmetros incompletos."
                        ]);

                        exit;
                    }
                }

                // ---------------------------------------
                // VERIFICANDO SE ESTÁ TENTANDO ATUALIZAR O MESMO OPERADOR EM DOIS LUGARES DISTINTOS
                // ---------------------------------------
                for ($i = 0; $i < count($dados['escala']); $i++) {
                    if ($dados['escala'][$i]['matricula'] > 5) {
                        if (count(array_values(array_filter($dados['escala'], fn ($element) => $element['matricula'] == $dados['escala'][$i]['matricula']))) > 1) {
                            echo json_encode([
                                "message" => "Tentando inserir operador válido em múltiplos equipamentos"
                            ]);
                            exit;
                        }
                    }
                }

                for ($i = 0; $i < count($dados['escala']); $i++) {
                    if (count(array_values(array_filter($dados['escala'], fn ($element) => $element['tag'] == $dados['escala'][$i]['tag']))) > 1) {
                        echo json_encode([
                            "message" => "Equipamento escalado em múltiplos campos."
                        ]);
                        exit;
                    }
                }

                $db = DB::connect();



                try {

                    // ---------------------------------------
                    // VERIFICANDO SE O OPERADOR EXISTE E É AUTORIZADO A OPERAR UM EQUIPAMENTO
                    // ---------------------------------------

                    for ($i = 0; $i < count($dados['escala']); $i++) {
                        $sql = $db->prepare('SELECT * from operadores where operadores.matricula = ?');
                        $sql->execute([$dados['escala'][$i]['matricula']]);
                        $operador = $sql->fetch(PDO::FETCH_ASSOC);

                        if (!$operador) {
                            echo json_encode([
                                "message" => "Operador {$dados['escala'][$i]['matricula']} não encontrado.",
                            ]);
                            exit;
                        }

                        $sql = $db->prepare('SELECT * from equipamentos where equipamentos.tag = ?');
                        $sql->execute([$dados['escala'][$i]['tag']]);
                        $equipamento = $sql->fetch(PDO::FETCH_ASSOC);


                        if (!$equipamento) {
                            echo json_encode([
                                "message" => "Equipamento {$dados['escala'][$i]['tag']} não encontrado.",
                            ]);
                            exit;
                        }

                        $categoria = $equipamento['categoria'];

                        if (!$operador[$categoria]) {
                            echo json_encode([
                                "message" => "Operador {$operador['nome']} - {$operador['matricula']} não é autorizado a operar {$equipamento['tag']}",
                            ]);
                            exit;
                        }
                    }


                    $db->beginTransaction();
                    foreach ($dados['escala'] as $valor) {

                        $sql = $db->prepare("SELECT * FROM operadorequipamento WHERE operadorequipamento.idlista = ? and operadorequipamento.tag = ?");
                        $sql->execute([$parametro, $valor['tag']]);
                        $obj = $sql->fetch(PDO::FETCH_ASSOC);

                        if (!$obj) {
                            throw new Exception('Escala não encontrada');
                        }

                        // ----------------------------------------
                        // VERIFICAR SE O OPERADOR QUE ESTÁ SAINDO DA ESCALA É VÁLIDO, SE SIM, ADICIONÁLO A LISTA FORA DE ESCALA
                        // ----------------------------------------
                        if ($obj['matricula'] > 5) {
                            $sql = $db->prepare("INSERT INTO operadorforaescala (matricula, idLista) VALUES (?,?)");
                            $sql->execute([$obj['matricula'], $parametro]);
                        }

                        // ----------------------------------------
                        // VERIFICAR SE O OPERADOR QUE ESTÁ SENDO ADICIONADO EXISTE NA LISTA FORA DE ESCALA
                        // ----------------------------------------
                        $sql = $db->prepare("SELECT * FROM operadorforaescala WHERE operadorforaescala.idlista = ? and operadorforaescala.matricula = ?");
                        $sql->execute([$parametro, $valor['matricula']]);
                        $obj = $sql->fetch(PDO::FETCH_ASSOC);

                        if ($obj) {
                            $sql = $db->prepare("DELETE FROM operadorforaescala WHERE operadorforaescala.idlista = ? and operadorforaescala.matricula = ?");
                            $sql->execute([$parametro, $valor['matricula']]);
                        }

                        $sql = $db->prepare("UPDATE operadorequipamento SET  matricula = ?, localizacao = ?, atividade = ?, transporte = ? WHERE operadorequipamento.idlista = ? and operadorequipamento.tag = ?");
                        $sql->execute([$valor['matricula'], $valor['localizacao'], $valor['atividade'], $valor['transporte'], $parametro, $valor['tag']]);
                    }

                    $db->commit();
                    echo json_encode([
                        "message" => "Dados atualizados com sucesso."
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        "message" => "Erro ao inserir os dados.",
                        "error" => $e->getMessage(),
                    ]);

                    $db->rollBack();
                }

                exit;
            }
        }
    }

    if ($metodo == "POST") {
        if ($acao == 'store' && $parametro == '') {

            // RECEBE UM JSON COM AS SEGUINTES CARACTERÍSTICAS:

            // {
            //     "nomeLista" : NOME DA TABELA,
            //     "turma": TURMA,
            //     "operadoresForaEscala" : [ARRAY DE MATRICULAS], 
            //     "escala" : [
            //         {
            //             "matricula": MATRICULA,
            //             "tag": TAG,
            //              "localizacao": LOCALIZAÇÃO
            //              "atividade": ATIVIDADE
            //              "transporte": TRANSPORTE   
            //         },
            //     ]
            // }

            // 1- adicionar os valores na tabela listaescala
            // 1.1- precisa receber um json com a lista de escala, um array com os operadores fora de escala, outro com os equipamentos fora de escala e outro com a escala.
            // 2 - adicionar os operadores fora de escala em sua respectiva tabela
            // 3 - adicionar os equipamentos fora de escala em sua respectiva tabela
            // 4 - adicionar a escala gerada em sua respectiva tabela

            // CONECTAR AO BANCO
            $db = DB::connect();

            $json = file_get_contents("php://input");
            $dados = json_decode($json, true);

            date_default_timezone_set("America/Sao_Paulo");
            $dataCriacao = date("Y-m-d");
            $horarioCriacao = date("H:i:s");

            if (!array_key_exists('nomeLista', $dados)) {
                echo json_encode([
                    "message" => "erro ao criar lista de escala"
                ]);
                exit;
            }
            if (!array_key_exists('turma', $dados)) {
                echo json_encode([
                    "message" => "erro ao criar lista de escala"
                ]);
                exit;
            }
            if (!array_key_exists('operadoresForaEscala', $dados)) {
                echo json_encode([
                    "message" => "erro ao criar lista de escala"
                ]);
                exit;
            }

            if (!array_key_exists('escala', $dados)) {
                echo json_encode([
                    "message" => "erro ao criar lista de escala"
                ]);
                exit;
            }

            foreach ($dados['escala'] as $escala) {
                if (!array_key_exists('matricula', $escala) || !array_key_exists('tag', $escala) || !array_key_exists('localizacao', $escala) || !array_key_exists('atividade', $escala) || !array_key_exists('transporte', $escala)) {
                    echo json_encode([
                        "message" => "Parâmetros incompletos."
                    ]);

                    exit;
                }
            }




            // ---------------------------------------
            // VERIFICANDO SE APENAS OPERADORES VÁLIDOS ESTÃO FORA DE ESCALA
            // ---------------------------------------
            for ($i = 0; $i < count($dados['operadoresForaEscala']); $i++) {
                if ($dados['operadoresForaEscala'][$i] <= 5) {
                    echo json_encode([
                        "message" => "Apenas operadores válidos podem ficar fora de escala"
                    ]);
                    exit;
                }
            }

            // ---------------------------------------
            // VERIFICANDO SE O OPERADOR ESTÁ ESCALADO EM MÚLTIPLOS EQUIPAMENTOS
            // ---------------------------------------
            for ($i = 0; $i < count($dados['escala']); $i++) {
                if ($dados['escala'][$i]['matricula'] > 5) {
                    if (count(array_values(array_filter($dados['escala'], fn ($element) => $element['matricula'] == $dados['escala'][$i]['matricula']))) > 1) {
                        echo json_encode([
                            "message" => "Tentando inserir operador válido em múltiplos equipamentos"
                        ]);
                        exit;
                    }
                }
            }



            // ---------------------------------------
            // VERIFICANDO SE O OPERADOR ESTA ESCALADO E FORA DE ESCALA SIMULTANEAMENTE
            // ---------------------------------------
            for ($i = 0; $i < count($dados['escala']); $i++) {
                if (array_search($dados['escala'][$i]['matricula'], $dados['operadoresForaEscala']) !== false) {
                    echo json_encode([
                        "message" => "Operador não pode está escalado e fora de escala simultaneamente"
                    ]);
                    exit;
                }
            }

            // ---------------------------------------
            // VERIFICANDO SE O EQUIPAMENTO ESTÁ EM MULTIPLOS CAMPOS
            // ---------------------------------------
            for ($i = 0; $i < count($dados['escala']); $i++) {
                if (count(array_values(array_filter($dados['escala'], fn ($element) => $element['tag'] == $dados['escala'][$i]['tag']))) > 1) {
                    echo json_encode([
                        "message" => "Equipamento escalado em múltiplos campos."
                    ]);
                    exit;
                }
            }


            try {

                // ---------------------------------------
                // VERIFICANDO SE O OPERADOR EXISTE E É AUTORIZADO A OPERAR UM EQUIPAMENTO
                // ---------------------------------------

                for ($i = 0; $i < count($dados['escala']); $i++) {
                    $sql = $db->prepare('SELECT * from operadores where operadores.matricula = ?');
                    $sql->execute([$dados['escala'][$i]['matricula']]);
                    $operador = $sql->fetch(PDO::FETCH_ASSOC);

                    if (!$operador) {
                        echo json_encode([
                            "message" => "Operador {$dados['escala'][$i]['matricula']} não encontrado.",
                        ]);
                        exit;
                    }

                    $sql = $db->prepare('SELECT * from equipamentos where equipamentos.tag = ?');
                    $sql->execute([$dados['escala'][$i]['tag']]);
                    $equipamento = $sql->fetch(PDO::FETCH_ASSOC);


                    if (!$equipamento) {
                        echo json_encode([
                            "message" => "Equipamento {$dados['escala'][$i]['tag']} não encontrado.",
                        ]);
                        exit;
                    }

                    $categoria = $equipamento['categoria'];

                    if (!$operador[$categoria]) {
                        echo json_encode([
                            "message" => "Operador {$operador['nome']} - {$operador['matricula']} não é autorizado a operar {$equipamento['tag']}",
                        ]);
                        exit;
                    }
                }

                // INICIA A TRANSAÇÃO
                $db->beginTransaction();

                // Inserir na tabela listaescalas
                $comando = "INSERT INTO listaescalas (nomeLista, horarioCriacao, dataCriacao, turma) VALUES (?,?,?,?)";
                $sql = $db->prepare($comando);
                // USANDO PREPARED STATEMENTS
                $sql->execute([$dados['nomeLista'], $horarioCriacao, $dataCriacao, $dados['turma']]);


                // PEGA O ULTIMO ID INSERIDO
                $idLista = $db->lastInsertId();


                // Inserir na tabela operadorforaescala
                $comando = "INSERT INTO operadorforaescala (matricula, idLista) VALUES (?,?)";
                $sql = $db->prepare($comando);

                foreach (array_values($dados['operadoresForaEscala']) as $valores) {
                    $sql->execute([$valores, $idLista]);
                }

                // Inserir na tabela operadorequipamento
                $comando = "INSERT INTO operadorequipamento (matricula, tag, idLista, localizacao, atividade, transporte) VALUES (?,?,?,?,?,?)";
                $sql = $db->prepare($comando);

                foreach (array_values($dados['escala']) as $valores) {
                    $sql->execute([$valores['matricula'], $valores['tag'], $idLista, $valores['localizacao'], $valores['atividade'], $valores['transporte']]);
                }


                // Confirma as alterações no banco de dados
                $db->commit();
                echo json_encode(["message" => "Dados inseridos com sucesso!"]);
            } catch (Exception $e) {
                $db->rollBack();
                // Em caso de erro, reverte as alterações
                echo json_encode([
                    "message" => "Erro ao inserir os dados.",
                    "error" => $e->getMessage(),
                ]);
            }

            exit;
        }
    }

    if ($metodo == 'DELETE') {
        if ($acao == 'delete') {
            if ($parametro != "") {
                $db = DB::connect();

                $sql = 'SELECT * FROM listaescalas where listaescalas.idlista = ?';
                $sql = $db->prepare($sql);
                $sql->execute([$parametro]);
                $obj = $sql->fetch(PDO::FETCH_ASSOC);

                if (!$obj) {
                    echo json_encode([
                        "message" => "Não foi possível encontrar a lista"
                    ]);
                    exit;
                }


                // -----------------------------
                // VERIFICA SE EXISTE REFERENCIA NA TABELA DE OPERADOREQUIPAMENTO
                // -----------------------------
                $sql = 'SELECT * FROM operadorequipamento where operadorequipamento.idlista = ?';
                $sql = $db->prepare($sql);
                $sql->execute([$parametro]);
                $obj = $sql->fetch(PDO::FETCH_ASSOC);

                if ($obj) {
                    $sql = 'DELETE FROM operadorequipamento WHERE operadorequipamento.idlista = ?';
                    $sql = $db->prepare($sql);
                    $sql->execute([$parametro]);
                }


                // -----------------------------
                // VERIFICA SE EXISTE REFERENCIA NA TABELA DE OPERADORFORAESCALA
                // -----------------------------
                $sql = 'SELECT * FROM operadorforaescala where operadorforaescala.idlista = ?';
                $sql = $db->prepare($sql);
                $sql->execute([$parametro]);
                $obj = $sql->fetch(PDO::FETCH_ASSOC);

                if ($obj) {
                    $sql = 'DELETE FROM operadorforaescala WHERE operadorforaescala.idlista = ?';
                    $sql = $db->prepare($sql);
                    $sql->execute([$parametro]);
                }


                $sql = 'DELETE FROM listaescalas WHERE listaescalas.idlista = ?';
                $sql = $db->prepare($sql);
                $sql->execute([$parametro]);

                echo json_encode(["message" => "Dados apagados com sucesso!"]);
            }
        }
    }
}