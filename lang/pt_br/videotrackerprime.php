<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * videotrackerprime.php
 *
 * @package   mod_videotrackerprime
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addeventcurrent'] = 'Adicionar evento neste ponto';
$string['advanced'] = 'Disponibilidade avançada';
$string['availablefrom'] = 'Disponível a partir de (Unix timestamp, 0 = sempre)';
$string['availableuntil'] = 'Disponível até (Unix timestamp, 0 = sempre)';
$string['averageconfidence'] = 'Confiança média';
$string['backtoactivity'] = 'Voltar para a atividade';
$string['checkpointanalytics'] = 'Analytics dos checkpoints';
$string['checkpointnotice'] = 'Salve a atividade e depois use “Gerenciar checkpoints” para distribuir os eventos pedagógicos na timeline.';
$string['checkpointscompleted'] = 'Checkpoints concluídos';
$string['completionallrequired'] = 'Concluir todos os checkpoints obrigatórios';
$string['completioncheckpointcount'] = 'Concluir pelo menos esta quantidade de checkpoints';
$string['completiondetail:allrequired'] = 'Concluir todos os checkpoints obrigatórios';
$string['completiondetail:count'] = 'Concluir pelo menos {$a} checkpoints';
$string['completiondetail:percent'] = 'Assistir pelo menos {$a}% do vídeo';
$string['completionpercent'] = 'Percentual mínimo assistido';
$string['completionpercent_help'] = 'Use 0 para desabilitar a regra. O progresso vem do local_video_bridge e trechos pulados não contam como assistidos.';
$string['confidencequestion'] = 'Quanto você entendeu este conteúdo?';
$string['confirmunderstood'] = 'Entendi este conceito';
$string['continue'] = 'Continuar';
$string['dashboard'] = 'Dashboard';
$string['deletecheckpointconfirm'] = 'Excluir este checkpoint e as interações dos alunos relacionadas a ele?';
$string['dismiss'] = 'Dispensar';
$string['dismissible'] = 'Permitir dispensar';
$string['distribution'] = 'Distribuição das respostas';
$string['duplicate'] = 'Duplicar';
$string['eventcheckpointcompleted'] = 'Checkpoint concluído';
$string['events'] = 'Eventos';
$string['eventtext'] = 'Texto';
$string['eventtitle'] = 'Título';
$string['eventtype'] = 'Tipo';
$string['managecheckpoints'] = 'Gerenciar checkpoints';
$string['maxchars'] = 'Máximo de caracteres';
$string['modulename'] = 'Video Tracker Prime';
$string['modulenameplural'] = 'Video Tracker Prime';
$string['nocheckpoints'] = 'Ainda não há checkpoints cadastrados.';
$string['notrackingsources'] = 'Nenhum provider do Video Bridge com tracking confiável está disponível.';
$string['nousers'] = 'Nenhum aluno está disponível no grupo atual.';
$string['onceonly'] = 'Aparecer somente uma vez na sessão atual';
$string['pausevideo'] = 'Pausar vídeo';
$string['playbackcontrolrequired'] = 'Este checkpoint exige um provider com a capability playbackcontrol.';
$string['playbackcontrolwarning'] = 'O provider selecionado não garante controle de reprodução. Pausa automática e bloqueio até interação ficam desabilitados.';
$string['pluginname'] = 'Video Tracker Prime';
$string['polloptions'] = 'Opções do poll, uma por linha';
$string['privacy:answerspath'] = 'Interações com checkpoints';
$string['privacy:metadata:answers'] = 'Interações do aluno com checkpoints.';
$string['privacy:metadata:answers:completed'] = 'Indica se a interação com o checkpoint foi concluída.';
$string['privacy:metadata:answers:response'] = 'Resposta do aluno quando o tipo de checkpoint aceita resposta.';
$string['privacy:metadata:answers:sessionid'] = 'Identificador da sessão de reprodução no navegador.';
$string['privacy:metadata:answers:timecreated'] = 'Momento em que a interação foi criada.';
$string['privacy:metadata:answers:timemodified'] = 'Momento da última alteração.';
$string['privacy:metadata:answers:userid'] = 'Aluno que interagiu com o checkpoint.';
$string['privacy:metadata:answers:videotimestamp'] = 'Timestamp efetivo do vídeo quando o checkpoint foi alcançado.';
$string['reflections'] = 'Reflexões';
$string['replaynewsession'] = 'Aparecer novamente em uma nova sessão';
$string['required'] = 'Checkpoint obrigatório';
$string['requiredcompleted'] = 'Checkpoints obrigatórios';
$string['requireinteraction'] = 'Exigir interação antes de continuar';
$string['resourcelabel'] = 'Texto do link';
$string['resourceurl'] = 'URL do recurso';
$string['saveresponse'] = 'Salvar resposta';
$string['sortorder'] = 'Ordem';
$string['student'] = 'Aluno';
$string['students'] = 'Alunos';
$string['studentspassed'] = 'Alunos que passaram por este ponto';
$string['timeline'] = 'Timeline pedagógica';
$string['timestamp'] = 'Timestamp (segundos)';
$string['type:alert'] = 'Alerta';
$string['type:checkpoint'] = 'Checkpoint de passagem';
$string['type:confidence'] = 'Escala de confiança';
$string['type:confirmation'] = 'Confirmação';
$string['type:message'] = 'Mensagem';
$string['type:poll'] = 'Poll';
$string['type:reflection'] = 'Reflexão curta';
$string['type:resource'] = 'Link/recurso';
$string['videoheader'] = 'Vídeo';
$string['videosource'] = 'Fonte do vídeo';
$string['videotrackerprime:addinstance'] = 'Adicionar uma atividade Video Tracker Prime';
$string['videotrackerprime:managecheckpoints'] = 'Gerenciar checkpoints da timeline';
$string['videotrackerprime:view'] = 'Visualizar Video Tracker Prime';
$string['videotrackerprime:viewreport'] = 'Visualizar dashboard de checkpoints';
$string['videotrackerprimename'] = 'Nome da atividade';
$string['visibleontimeline'] = 'Visível na timeline';
$string['watched'] = 'Assistido';
$string['yourreflection'] = 'Sua reflexão';
