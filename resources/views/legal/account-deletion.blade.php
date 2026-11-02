@extends('legal.layout')
@section('title', 'Excluir conta')
@section('description', 'Como solicitar a exclusão da sua conta e dos seus dados no OrbGem.')
@section('content')
<h1>Excluir conta OrbGem</h1>
<p class="muted">Aplica-se ao aplicativo OrbGem para Android e ao site.</p>

<h2>Pelo aplicativo</h2>
<ol>
    <li>Abra o OrbGem e entre na sua conta.</li>
    <li>Vá em <strong>Mais</strong> → <strong>Excluir conta</strong>.</li>
    <li>Confirme com a sua senha.</li>
</ol>

<h2>Sem acessar o aplicativo</h2>
<p>Envie um e-mail para <a href="mailto:{{ config('app.support_email') }}?subject=Exclus%C3%A3o%20de%20conta%20OrbGem">{{ config('app.support_email') }}</a>, a partir do endereço cadastrado na conta, com o assunto "Exclusão de conta OrbGem". Confirmaremos a identidade e concluiremos a exclusão em até 15 dias.</p>

<h2>O que é excluído</h2>
<ul>
    <li>Seus dados de cadastro (nome, e-mail, senha), sessões e tokens de notificação.</li>
    <li>As carteiras de que você é a única pessoa participante, com contas, cartões, lançamentos, investimentos, metas e demais dados financeiros.</li>
    <li>As conexões com instituições financeiras (Open Finance) dessas carteiras.</li>
</ul>

<h2>O que pode permanecer</h2>
<ul>
    <li>Se você participa da carteira de outra pessoa, seus dados pessoais são anonimizados. Os lançamentos feitos por você continuam na carteira dela, sem identificar você.</li>
    <li>Dados que a legislação nos obrigue a guardar, pelo prazo exigido.</li>
</ul>

<h2>Antes de excluir</h2>
<p>Se você é proprietário de uma carteira compartilhada com outras pessoas, transfira a propriedade ou remova os membros. Sem isso, a exclusão não pode ser concluída. A exclusão é definitiva e não pode ser desfeita.</p>
@endsection
