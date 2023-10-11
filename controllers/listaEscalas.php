<?php
if ($api == 'listaEscalas') {
    if ($metodo == 'GET') {
        if ($acao == 'index' && $parametro == '') {
            $db = DB::connect();
            $sql = $db->prepare("SELECT * FROM listaescalas");
            $sql->execute();
            $obj = $sql->fetchAll(PDO::FETCH_ASSOC);

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
            $sql = $db->prepare("SELECT * FROM listaescalas WHERE listaescalas.idlista = {$parametro}");
            $sql->execute();
            $obj = $sql->fetchObject();

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
    }

    if ($metodo == 'PUT') {
        if ($acao == 'update') {
            if ($parametro != "") {

                // 1- VERIFICAR SE EXISTE O DADO PARA MODIFICAR;
                // 2- MODIFICAR 
                // 3 - RETORNAR A MENSAGEM DE ERRO OU SUCESSOR

                $db = DB::connect();

                try {
                    $sql = $db->prepare("SELECT * FROM listaescalas WHERE listaescala.idlista = '{$parametro}'");
                    $sql->execute();
                } catch (Exception $e) {
                    echo json_encode(["message" => "Não foi possível encontrar a lista"]);
                    exit;
                }

                // $obj = $sql->fetchObject();

                // if (!$obj) {
                // }


                $json = file_get_contents("php://input");
                $dados = json_decode($json, true);

                $sql = "UPDATE listaescala SET ";

                $contador = 1;
                foreach (array_keys($dados) as $key) {
                    if (count($dados) > $contador) {
                        $sql .= "$key = '{$dados[$key]}', ";
                    } else {
                        $sql .= "$key = '{$dados[$key]}' ";
                    }

                    $contador++;
                }

                $sql .= "WHERE listaescala.idlista = '$parametro'";

                // echo $sql;
                $exec = $db->prepare($sql);

                try {
                    $response = $exec->execute();
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

    if ($metodo == "POST") {
        if ($acao == 'store' && $parametro == '') {

            echo "estamos no metodo post, store";
            exit;
        }
    }
}
