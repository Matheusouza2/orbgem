@extends('legal.layout')
@section('title', 'Termos de Uso')
@section('description', 'Condições para usar o aplicativo e o site OrbGem.')
@section('content')
<h1>Termos de Uso</h1>
<p class="muted">Última atualização: 2 de novembro de 2026</p>

<h2>1. O serviço</h2>
<p>O OrbGem é uma ferramenta de organização financeira pessoal e familiar. Ele não é instituição financeira, não movimenta dinheiro e não oferece aconselhamento de investimento. Resumos e respostas gerados por inteligência artificial são informativos e podem conter imprecisões.</p>

<h2>2. Conta</h2>
<p>Você deve fornecer informações verdadeiras, manter sua senha em sigilo e ter 18 anos ou mais. Você é responsável pelas atividades realizadas com a sua conta.</p>

<h2>3. Uso aceitável</h2>
<p>É proibido usar o serviço para fins ilícitos, tentar acessar dados de terceiros, interferir no funcionamento da plataforma ou contornar medidas de segurança.</p>

<h2>4. Carteiras compartilhadas</h2>
<p>Ao convidar membros, você define o papel (proprietário, editor ou leitor) e o que cada um pode ver ou alterar. Você é responsável por quem recebe acesso.</p>

<h2>5. Conexões bancárias</h2>
<p>A conexão com instituições é opcional, feita via Open Finance e pode ser desfeita a qualquer momento. A disponibilidade e a precisão dos dados dependem das instituições e do provedor.</p>

<h2>6. Disponibilidade</h2>
<p>Buscamos manter o serviço disponível, mas ele pode sofrer interrupções para manutenção ou por fatores fora do nosso controle. Confira informações importantes diretamente com suas instituições.</p>

<h2>7. Encerramento</h2>
<p>Você pode excluir sua conta quando quiser (veja <a href="{{ url('/excluir-conta') }}">Excluir conta</a>). Podemos suspender contas que violem estes termos.</p>

<h2>8. Privacidade</h2>
<p>O tratamento de dados pessoais segue a <a href="{{ url('/privacidade') }}">Política de Privacidade</a>.</p>

<h2>9. Lei aplicável</h2>
<p>Estes termos são regidos pelas leis do Brasil.</p>
@endsection
