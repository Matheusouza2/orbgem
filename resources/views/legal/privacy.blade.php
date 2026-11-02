@extends('legal.layout')
@section('title', 'Política de Privacidade')
@section('description', 'Como o OrbGem coleta, usa, compartilha e protege seus dados.')
@section('content')
<h1>Política de Privacidade</h1>
<p class="muted">Última atualização: 2 de novembro de 2026</p>
<p>Esta política explica como o aplicativo e o site OrbGem ("OrbGem", "nós") tratam seus dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).</p>

<h2>1. Dados que coletamos</h2>
<ul>
    <li><strong>Conta:</strong> nome, e-mail e senha (armazenada apenas como hash).</li>
    <li><strong>Dados financeiros que você informa:</strong> carteiras, contas, cartões de crédito, lançamentos, categorias, investimentos, metas e recorrências.</li>
    <li><strong>Dados bancários via Open Finance:</strong> se você optar por conectar uma instituição financeira, recebemos, por meio do provedor Pluggy, dados das contas, cartões, transações e investimentos autorizados por você. Nós não temos acesso às suas credenciais bancárias.</li>
    <li><strong>Dados do dispositivo:</strong> token de notificações push, plataforma (Android) e identificador do dispositivo, usados para enviar alertas.</li>
    <li><strong>Sessão:</strong> tokens de acesso que mantêm você autenticado, guardados de forma segura no aparelho.</li>
</ul>

<h2>2. Para que usamos os dados</h2>
<ul>
    <li>Prestar o serviço de gestão financeira pessoal e compartilhada (carteiras e membros).</li>
    <li>Autenticar você e proteger sua conta.</li>
    <li>Sincronizar dados das instituições que você conectou.</li>
    <li>Enviar notificações sobre sua carteira, como vencimentos e lembretes.</li>
    <li>Gerar resumos e respostas do assistente financeiro, quando você usa esse recurso.</li>
</ul>

<h2>3. Com quem compartilhamos</h2>
<p>Não vendemos seus dados e não usamos publicidade. Compartilhamos dados apenas com operadores necessários ao serviço:</p>
<ul>
    <li><strong>Pluggy</strong> — conexão com instituições financeiras (Open Finance).</li>
    <li><strong>Google Firebase Cloud Messaging</strong> — entrega de notificações push.</li>
    <li><strong>Provedores de inteligência artificial</strong> (Google Gemini e Groq) — processam o contexto financeiro enviado ao assistente e aos resumos.</li>
    <li><strong>Brapi</strong> — cotações de ativos.</li>
    <li>Outros membros de uma carteira compartilhada veem os dados dessa carteira, conforme o papel que você atribuiu a eles.</li>
    <li>Autoridades, quando houver obrigação legal.</li>
</ul>

<h2>4. Segurança</h2>
<p>A comunicação com nossos servidores usa HTTPS. Senhas são armazenadas com hash e o token de sessão fica no armazenamento seguro do dispositivo. Nenhum método é totalmente infalível, mas adotamos medidas técnicas para reduzir riscos.</p>

<h2>5. Retenção e exclusão</h2>
<p>Mantemos seus dados enquanto sua conta existir. Você pode excluir a conta a qualquer momento, no aplicativo (Mais → Excluir conta) ou pela página <a href="{{ url('/excluir-conta') }}">Excluir conta</a>. A exclusão remove seus dados pessoais e as carteiras de que você é a única pessoa participante, e desconecta as instituições vinculadas. Se você participa de uma carteira de outra pessoa, seus dados pessoais são anonimizados, e os lançamentos feitos por você permanecem na carteira dela sem identificar você. Dados que a lei nos obrigue a guardar poderão ser mantidos pelo prazo legal.</p>

<h2>6. Seus direitos</h2>
<p>Você pode solicitar confirmação de tratamento, acesso, correção, anonimização, portabilidade, eliminação e informação sobre compartilhamento, além de revogar consentimentos. Escreva para <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>.</p>

<h2>7. Crianças</h2>
<p>O OrbGem não é direcionado a menores de 18 anos.</p>

<h2>8. Alterações</h2>
<p>Podemos atualizar esta política. Alterações relevantes serão comunicadas no aplicativo ou por e-mail.</p>
@endsection
