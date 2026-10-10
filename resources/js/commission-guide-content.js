const step = (id, target, title, text, optional = false) => ({ id, target, title, text, optional });
const end = (id, text) => step(id, null, 'Orientações concluídas', text);

export const guides = {
    welcome: { title: 'Boas-vindas', steps: [
        step('welcome', null, 'Bem-vindo ao ambiente das comissões', 'Vamos apresentar as ferramentas disponíveis para sua atuação no Prêmio. Você pode acompanhar as orientações agora e voltar a acessar o guia a qualquer momento pelo botão Rever orientações.'),
        step('menu', '#sidebar_menu', 'Suas funcionalidades', 'Utilize o menu para acessar as funcionalidades disponíveis para o seu perfil. Ao entrar em uma funcionalidade pela primeira vez, você poderá conhecer suas etapas.', true),
        step('help', '[data-guide-help]', 'Rever orientações', 'As orientações continuam disponíveis em Rever orientações. Cada funcionalidade tem seu próprio guia.'),
    ] },
    'evaluator-list': { title: 'Avaliador — inscrições', steps: [
        step('AV-01', '#content', 'Suas avaliações', 'Aqui estão as inscrições que você pode avaliar.'),
        step('AV-02', '#search-form', 'Encontre a inscrição', 'Use a pesquisa ou os filtros para encontrar uma inscrição.'),
        step('AV-03', '[data-guide-action="Avaliar"], [data-guide-actions-toggle]', 'Inicie a análise', 'Clique em Avaliar para abrir a inscrição. Se necessário, abra Gerenciar primeiro.', true),
        step('AV-empty', '[data-guide-empty]', 'Nenhuma inscrição disponível', 'No momento, não há inscrições disponíveis para sua atuação nesta tela. Confira os filtros, sua vinculação à comissão e o período da etapa com a organização do Prêmio.', true),
        end('AV-list-end', 'Você conhece a listagem. Abra uma inscrição quando quiser continuar com o guia da avaliação.'),
    ] },
    'evaluator-detail': { title: 'Avaliador — análise', steps: [
        step('AV-04', '[data-guide-registration-content]', 'Conheça a proposta', 'Abra as seções e leia a proposta.'),
        step('AV-05', '[data-guide-files]', 'Consulte os documentos', 'Abra os documentos para complementar sua análise.', true),
        step('AV-06', '[data-guide-evaluation-form]', 'Avalie pelos critérios da categoria', 'Leia cada critério e confira a nota mínima, máxima e o peso.'),
        step('AV-07', '[data-guide-score]', 'Defina a nota', 'Dê uma nota para cada critério.'),
        step('AV-08', '[data-guide-justification]', 'Explique sua avaliação', 'Explique o motivo de cada nota.'),
        step('AV-09', '[data-guide-evaluation-form] button[type="submit"]', 'Revise antes de enviar', 'Confira as notas e justificativas. Clique em Avaliar para enviar.'),
        end('AV-10', 'Agora você sabe como consultar a inscrição, analisar os critérios e registrar sua avaliação. Use Rever orientações sempre que precisar.'),
    ] },
    'indicator-list': { title: 'Indicador — inscrições', steps: [
        step('IN-01', '#content', 'Suas indicações', 'Aqui estão as inscrições que você pode indicar.'),
        step('IN-02', '#search-form', 'Encontre uma inscrição', 'Use a pesquisa ou os filtros para encontrar uma inscrição.'),
        step('IN-03', '[data-guide-action="Indicar"], [data-guide-actions-toggle]', 'Conheça a inscrição', 'Clique em Indicar para abrir a inscrição. Se necessário, abra Gerenciar primeiro.', true),
        step('IN-empty', '[data-guide-empty]', 'Nenhuma inscrição disponível', 'No momento, não há inscrições disponíveis para sua atuação nesta tela. Confira os filtros, sua vinculação à comissão e o período da etapa com a organização do Prêmio.', true),
        end('IN-list-end', 'Você conhece a listagem. Abra uma inscrição para conhecer as orientações de consulta.'),
    ] },
    'indicator-detail': { title: 'Indicador — consulta', steps: [
        step('IN-04', '[data-guide-registration-content]', 'Leia as informações apresentadas', 'Abra as seções e leia as informações da inscrição.'),
        step('IN-05', '[data-guide-files]', 'Confira os documentos', 'Abra os documentos para complementar sua análise.', true),
        step('IN-06', '[data-guide-indication-justification]', 'Explique sua indicação', 'Escreva por que esta inscrição merece ser indicada.'),
        step('IN-07', '[data-guide-indication-submit]', 'Envie sua indicação', 'Confira a justificativa e clique em Indicar Inscrição. O guia termina quando a indicação for enviada.'),
        end('IN-08', 'Consulte as inscrições com atenção e siga as orientações da comissão para registrar indicações. Você pode rever este guia quando precisar.'),
    ] },
    'judge-panel': { title: 'Julgador — seleção', steps: [
        step('JU-02', '.judging-current-category', 'Uma categoria por vez', 'Você vota em uma categoria por vez. Confira o nome e a descrição da categoria. Você pode voltar a acessar este guia a qualquer momento pelo botão Rever orientações.'),
        step('JU-03', '.judging-selection-count', 'Acompanhe suas escolhas', 'O contador mostra quantas inscrições você escolheu e quantas faltam.'),
        step('JU-04', '[data-card]', 'Abra os detalhes', 'Clique em uma inscrição para ler os detalhes.'),
        step('JU-09', '[data-card-check]', 'Selecione ou desmarque', 'Use o marcador do cartão para selecionar ou desmarcar uma inscrição.'),
        step('JU-10', '[data-clear-btn]', 'Revise suas escolhas', 'Use Limpar se quiser refazer as escolhas ainda não confirmadas.'),
        step('JU-11', '[data-confirm-btn]', 'Grave suas escolhas', 'Escolha a quantidade pedida. Clique em Confirmar votos: isso grava seu julgamento.'),
        end('JU-14', 'Agora você sabe como analisar as inscrições, selecionar suas escolhas e confirmar cada categoria. Use Rever orientações quando precisar.'),
    ] },
    'judge-detail': { title: 'Julgador — detalhes', steps: [
        step('JU-05', '[data-drawer-body] [data-guide-nominee-data]', 'Confira os dados', 'Confira os dados da inscrição e da categoria.'),
        step('JU-06', '[data-drawer-body]', 'Consulte as informações completas', 'Abra as seções e leia as informações.'),
        step('JU-07-files', '[data-drawer-body] [data-guide-files]', 'Consulte os anexos', 'Abra os anexos para ajudar na sua decisão.', true),
        step('JU-07-opinions', '[data-drawer-body] [data-guide-opinions]', 'Consulte as avaliações', 'Confira as avaliações e indicações disponíveis.', true),
        step('JU-08', '[data-drawer-confirm]', 'Inclua esta inscrição na seleção', 'Clique em Escolher esta para selecionar. Você ainda precisa confirmar as escolhas no painel.'),
        end('JU-detail-end', 'Você conhece os detalhes da inscrição. Concluir este guia não seleciona a inscrição nem confirma votos.'),
    ] },
    'judge-votes': { title: 'Julgador — acompanhamento', steps: [
        step('JU-12', '[aria-labelledby="votos-title"]', 'Acompanhe as categorias confirmadas', 'Esta área apresenta o acompanhamento das categorias em que você já confirmou suas escolhas. Os votos da categoria em julgamento não são exibidos antes da sua confirmação.'),
    ] },
    'judge-empty': { title: 'Julgador — disponibilidade', steps: [
        step('JU-empty', '[data-guide-judge-empty]', 'Nenhuma categoria disponível', 'No momento, não há categoria disponível para julgamento. A disponibilidade depende do período do Prêmio, das inscrições habilitadas para esta etapa e das escolhas já confirmadas.'),
    ] },
    'judge-done': { title: 'Julgador — conclusão', steps: [
        step('JU-13', '.judging-completed', 'Confira o resumo das suas escolhas', 'O resumo apresenta suas escolhas por categoria, com o identificador e o nome do indicado ou título do projeto. Confira os registros apresentados.'),
    ] },
};

export function contextGuides(page, profiles, { drawer = false, panel = false, done = false, empty = false, votes = false, detail = false } = {}) {
    const list = [];
    if (page === 'panel.julgar.index' && profiles.includes('judge')) {
        if (drawer) return ['judge-detail'];
        if (panel) list.push('judge-panel');
        if (done) list.push('judge-done');
        if (empty) list.push('judge-empty');
        if (votes) list.push('judge-votes');
    }
    if (page === 'admin.registration.index') {
        if (profiles.includes('evaluator')) list.push('evaluator-list');
        if (profiles.includes('indicator')) list.push('indicator-list');
    }
    if (page === 'admin.registration.show' && detail) {
        if (profiles.includes('evaluator')) list.push('evaluator-detail');
        if (profiles.includes('indicator')) list.push('indicator-detail');
    }
    return list;
}

export function pendingGuides(ids, progress) {
    return ids.filter(id => progress[id]?.status !== 'completed');
}

export function automaticGuide(ids, profiles) {
    for (const profile of profiles) {
        const id = ids.find(id => id.startsWith(`${profile}-`));
        if (id) return id;
    }
    return undefined;
}

export const taskSteps = {
    'evaluator-list': 'AV-03', 'indicator-list': 'IN-03',
    'evaluator-detail': 'AV-09', 'indicator-detail': 'IN-07',
    'judge-panel': 'JU-11', 'judge-detail': 'JU-08',
};
