<?php
if ($api == 'equipamentos') {
    if ($metodo == 'GET') {

        if ($acao == 'index' && $parametro == '') {

            echo "equipamentos/index";
            exit;

            $db = DB::connect();
            $sql = $db->prepare("SELECT equipamentos.tag, equipamentos.categoria, equipamentos.disponivel FROM equipamentos");
            $sql->execute();
            $obj = $sql->fetchAll(PDO::FETCH_ASSOC);

            if (!$obj) {
                $response = array(
                    "message" => "Nenhum equipamento encontrado!"
                );
                echo json_encode($response);
                exit;
            }

            echo json_encode($obj);
            exit;
        }

        if ($acao == 'show' && $parametro != '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT equipamentos.tag, equipamentos.categoria, equipamentos.disponivel FROM equipamentos WHERE equipamentos.tag = '{$parametro}'");
            $sql->execute();
            $obj = $sql->fetchObject();

            if ($obj) {
                echo json_encode($obj);
            } else {
                $response = array(
                    "message" => "Nenhum equipamento encontrado!"
                );
                echo json_encode($response);
            }
            exit;
        }
    }

    if ($metodo == 'PUT') {
        if ($acao == 'update') {
            if ($parametro != "") {

                // 1- VERIFICAR SE EXISTE O DADO PARA MODIFICAR;
                // 2- MODIFICAR 
                // 3 - RETORNAR A MENSAGEM DE ERRO OU SUCESSOR

                $db = DB::connect();
                $sql = $db->prepare("SELECT * FROM equipamentos WHERE equipamentos.tag = '{$parametro}'");
                $sql->execute();
                $obj = $sql->fetchObject();

                if (!$obj) {
                    echo json_encode(["message" => "Não foi possível encontrar o equipamento"]);
                    exit;
                }


                $json = file_get_contents("php://input");
                $dados = json_decode($json, true);


                if (!array_key_exists('disponivel', $dados)) {
                    echo json_encode([
                        "message" => "Parâmetros ausentes"
                    ]);
                    exit;
                }

                if ($dados['disponivel'] < 0 || $dados['disponivel'] > 1 || $dados['disponivel'] == null) {
                    echo json_encode([
                        "message" => '"disponivel" precisa ser do tipo booleano'
                    ]);
                    exit;
                }


                $exec = $db->prepare("UPDATE equipamentos set disponivel = ? where equipamentos.tag = ?");

                try {
                    $response = $exec->execute([$dados['disponivel'], $parametro]);
                    echo json_encode(["message" => "Dados atualizados com sucesso!"]);
                } catch (Exception $e) {
                    echo json_encode([
                        "message" => "Erro ao atualizar os dados!",
                        "error" => $e->getMessage(),
                    ]);
                }

                exit;
            }
        }
    }
}
