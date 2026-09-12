# Manual dos Componentes de Input

## Importação

```jsx
import Inputs from "@/Components/Inputs";
```

Todos os componentes usam `React.lazy()`. Acesse via dot notation: `Inputs.Validation`, `Inputs.Mask`, etc.

## Dois modos de operação

**Modo Form** (padrão para Inertia `useForm`):

```jsx
const { data, setData, errors } = useForm({ nome: "" });

<Inputs.Validation name="nome" label="Nome" value={data.nome} setData={setData} errors={errors} />
```

**Modo Controlado** (para filtros de tabela, estados externos):

```jsx
<Inputs.Validation name="nome" label="Nome" value={inputValue.nome} onChange={(e) => handleFilterChange(e)} labelLight />
```

---

## Inputs.Validation — Input de texto

```jsx
<Inputs.Validation
  name="razao_social"      // obrigatório — id, htmlFor e chave em errors
  label="Razão Social"     // obrigatório
  value={data.razao_social} // obrigatório
  setData={setData}        // modo form — use OU onChange, nunca ambos
  errors={errors}          // objeto de erros do useForm
  onChange={handleFilter}   // modo controlado
  type="text"              // text | password | email | number (default: text)
  labelLight               // label claro para fundo escuro (filtros)
  clearable                // botão X para limpar
  readOnly
  disabled
  maxLength={4}
  className="w-full"
/>
```

Exemplos reais:
```jsx
// Formulário
<Inputs.Validation name="razao_social" label="Razão Social" value={data.razao_social} setData={setData} errors={errors} />
<Inputs.Validation type="password" name="password" label="Senha" setData={setData} errors={errors} />

// Filtro
<Inputs.Validation name="descricao" label="Descrição" value={inputValue.descricao} onChange={(e) => handleFilterChange(e)} labelLight />

// Aninhado com dot notation
<Inputs.Validation name="endereco.logradouro" label="Logradouro" value={data.endereco?.logradouro} setData={setData} errors={errors} />

// Dinâmico em array
<Inputs.Validation name={`equipamentos[${index}].unidade`} value={equipamento.unidade} setData={setData} errors={errors} />
```

---

## Inputs.Color — Seletor de cor

Seletor de cor para formulários Inertia. Mantém o mesmo contrato dos demais inputs compartilhados.

```jsx
<Inputs.Color name="cor" label="Cor" value={data.cor} setData={setData} errors={errors} />
```

---

## Inputs.Mask — Input com máscara

Extende Validation. Adicione a prop `mask`:

```jsx
<Inputs.Mask mask="tel" name="telefone" label="Telefone" value={data.telefone} setData={setData} errors={errors} />
```

### Máscaras disponíveis

| mask | Formato | Exemplo |
|------|---------|---------|
| `cpf` | `___.___.___-__` | `123.456.789-00` |
| `cnpj` | `__.___.___/____-__` | `12.345.678/0001-90` |
| `cgc` | auto-detecta CPF/CNPJ | `123.456.789-00` ou `12.345.678/0001-90` |
| `cep` | `__.___-___` | `01.001-000` |
| `tel` | `(__) _____-____` | `(11) 91234-5678` |
| `date` | `__/__/____` | `01/12/2026` |
| `cartao` | `____ ____ ____ ____` | `1234 5678 9012 3456` |
| `placa` | `___-____` | `ABC-1234` |
| `ano` | `____` | `2026` |
| `boleto` | formato boleto | `23793.38128 60000...` |
| `aleatoria` | UUID alfanumérico | `a1b2c3d4-e5f6...` |
| *(default)* | aceita `[a-zA-Z0-9@._-]` | — |

Aceita todas as props de `Inputs.Validation`.

---

## Inputs.Flatpickr — Datepicker

Locale pt_BR pré-configurado. Suporta data única, range e mês/ano.

```jsx
<Inputs.Flatpickr
  name="data_inicio"       // obrigatório
  label="Data de início"   // obrigatório
  value={data.data_inicio} // "YYYY-MM-DD" | ["YYYY-MM-DD","YYYY-MM-DD"] | "YYYY-MM"
  setData={setData}
  errors={errors}
  mode="single"            // "single" (default) | "range"
  monthYearOnly            // selector mês/ano
  clearable                // botão de limpar
  disabled
  labelLight               // label claro para fundo escuro
  mindate="2026-01-01"
  maxdate="2026-12-31"
  placeholder="Selecione..."
  className="w-64"
  onChange={callback}       // modo controlado
/>
```

**Formatos de value:**
- `single`: `"2026-09-01"`
- `range`: `["2026-09-01", "2026-09-30"]`
- `monthYearOnly`: `"2026-09"`

```jsx
// Data simples
<Inputs.Flatpickr label="Data de início" name="data_inicio" value={data.data_inicio} setData={setData} errors={errors} />

// Mês/ano
<Inputs.Flatpickr label="Validade" name="validade" value={data.validade} setData={setData} errors={errors} monthYearOnly />

// Range
<Inputs.Flatpickr label="Período" name="periodo" value={data.periodo} setData={setData} mode="range" clearable />

// Range em filtro
<Inputs.Flatpickr name="vencimento" label="Vencimento" value={inputValue.vencimento} onChange={(e) => handleFilterChange(e, 'vencimento', false, true)} mode="range" clearable labelLight />
```

---

## Inputs.Number — Input numérico formatado

Exibe valor formatado (`R$ 1.234,56`) mas envia valor numérico puro ao form.

```jsx
<Inputs.Number
  name="valor"
  label="Valor"
  value={data.valor}        // string ou number
  setData={setData}
  errors={errors}
  format="currency"         // "currency" (default) | "decimal"
  maxDigits={2}             // casas decimais (default: 2)
  readOnly
  disabled
  clearable
  labelLight
  onChange={callback}        // modo controlado
  onBlur={calculateTotal}   // callback ao perder foco
/>
```

**Valores enviados:**
- `format="currency"`, `maxDigits=2` → `"1234.56"`
- `format="decimal"`, `maxDigits=0` → `"1234"`
- `format="decimal"`, `maxDigits=4` → `"1234.5678"`

```jsx
// Moeda
<Inputs.Number name="valor" label="Valor" value={data.valor} setData={setData} errors={errors} format="currency" />

// Decimal 4 casas
<Inputs.Number name="litros" label="Litros" value={data.litros} setData={setData} errors={errors} format="decimal" maxDigits={4} />

// Inteiro
<Inputs.Number name="km" label="Km" value={data.km} setData={setData} errors={errors} format="decimal" maxDigits={0} />

// Com onBlur
<Inputs.Number name="qtd" label="Qtd" value={data.qtd} setData={setData} errors={errors} format="decimal" maxDigits={4} onBlur={calculateTotal} />
```

---

## Inputs.Select — Select síncrono ou async

Usa `react-select`. Para async, passe `async` + `loadOptions`.

```jsx
<Inputs.Select
  name="fornecedor_id"      // obrigatório
  label="Fornecedor"         // obrigatório
  value={data.fornecedor}    // { value, label } ou null
  setData={setData}
  errors={errors}
  async                      // habilita AsyncSelect
  loadOptions={buscarFornecedores}  // (inputValue, callback) => void
  altName="fornecedor"       // salva objeto completo em altName, value em name
  isMulti                    // seleção múltipla
  isClearable                // default: true
  options={[{ value: 1, label: "Opção" }]} // opções estáticas (sem async)
  labelLight                 // label claro
  menuPlacement="auto"
  readOnly
  disabled
  required
  onChange={callback}         // modo controlado
  key={fornecedorId}          // força re-render
/>
```

**altName:** Quando definido, `setData(name, selectedOption.value)` + `setData(altName, selectedOption)`.

```jsx
// Async com altName
<Inputs.Select async label="Fornecedor" altName="fornecedor" name="fornecedor_id" value={data.fornecedor} setData={setData} errors={errors} loadOptions={buscarFornecedor} />

// Síncrono
<Inputs.Select name="passivo" label="Passivo" value={inputValue.passivo} onChange={(e) => handleFilterChange(e, 'passivo')} options={[{ value: 1, label: "Circulante" }]} labelLight />

// Múltiplo
<Inputs.Select async altName="ordem_compra" name="ordem_compra_id" label="Ordem de compra" errors={errors} setData={setData} value={data.ordem_compra} loadOptions={loadOrdens} isMulti />
```

---

## Inputs.Search — Input com botão de busca

InputMask + botão de lupa à direita. Ideal para CEP, CNPJ, etc.

```jsx
<Inputs.Search
  clickButton={handleCnpj}   // obrigatório — callback ao clicar
  mask="cgc"                  // obrigatório — mesma tabela de Inputs.Mask
  name="cnpj"
  label="CNPJ/CPF"
  value={data.cnpj}
  setData={setData}
  errors={errors}
  loading={false}             // true = exibe ícone de loading ao invés da lupa
/>
```

```jsx
// CNPJ
<Inputs.Search clickButton={handleCnpj} mask="cgc" name="cnpj" label="CNPJ/CPF" value={data.cnpj} setData={setData} errors={errors} />

// CEP com loading
<Inputs.Search clickButton={handleCep} mask="cep" name="endereco.cep" label="CEP" value={data.endereco?.cep} setData={setData} errors={errors} loading={loadingCep} />
```

---

## Inputs.CreatableSelect — Select que permite criar opções

AsyncCreatableSelect do `react-select`. Permite criar novas opções inline.

```jsx
<Inputs.CreatableSelect
  name="unidade_id"
  label="Unidade"
  value={data.unidade}       // { value, label } ou null
  setData={setData}
  errors={errors}
  loadOptions={buscarUnidade}
  altName="unidade"
  async
  formatCreateLabel={(e) => `Criar "${e}"`}  // default: 'Criar categoria "${e}"'
  onChange={callback}
/>
```

```jsx
// Criar unidade
<Inputs.CreatableSelect altName="unidade" name="unidade_id" label="Unidade" value={data.unidade} setData={setData} errors={errors} loadOptions={buscarUnidade} />

// Criar categoria
<Inputs.CreatableSelect label="Categoria" name="categoria_id" value={data.categoria} loadOptions={buscarCategoria} onChange={(opt) => handleSelectChange("categoria", opt, "categoria_id")} />
```

---

## Inputs.Textarea — Área de texto

Wrapper do Flowbite `Textarea`. **Sempre usa `setData`** (não suporta `onChange` separado).

```jsx
<Inputs.Textarea
  name="observacoes"
  label="Observações"
  value={data.observacoes}
  setData={setData}
  errors={errors}
  rows={8}                   // linhas visíveis
  disabled
/>
```

```jsx
<Inputs.Textarea label="Observações" name="observacoes" value={data.observacoes} setData={setData} errors={errors} />
<Inputs.Textarea label="Descrição" name="descricao" rows={8} setData={setData} errors={errors} value={data.descricao} />
```

---

## Inputs.FileInput — Upload de arquivo (recomendado)

Input de arquivo nativo estilizado.

```jsx
<Inputs.FileInput
  name="anexo"
  label="Anexar Nota"
  setData={setData}
  errors={errors}
  multiple                   // aceita múltiplos arquivos
  value=""
/>
```

**Comportamento:** 1 arquivo → `setData(name, file)`. Múltiplos → `setData(name + "[]", Array.from(files))`.

```jsx
<Inputs.FileInput label="Assinatura" name="assinatura" setData={setData} />
<Inputs.FileInput name="anexo" label="Anexar Nota" errors={errors} setData={setData} />
<Inputs.FileInput label="Anexos" name="anexo" setData={setData} errors={errors} multiple />
```

---

## Inputs.File — Upload com drag-and-drop

Área de upload com drag-and-drop. **Não utilizado no código atual.**

```jsx
<Inputs.File label="Documentos" name="documentos" setData={setData} errors={errors} multiple />
```

---

## Inputs.AddonSelect — Select + Input lado a lado

Select à esquerda + input com máscara à direita. **Não utilizado no código atual.**

```jsx
<Inputs.AddonSelect
  name="ddd_telefone"
  label="Telefone"
  setData={setData}
  errors={errors}
  mask="(__) _____-____"
  options={[{ value: "11", label: "11" }, { value: "21", label: "21" }]}
/>
```

---

## Referência rápida

| Componente | Usar para | Props-chave |
|---|---|---|
| `Validation` | Texto, senha, email | `name`, `label`, `value`, `setData`/`onChange`, `errors` |
| `Mask` | CPF, CNPJ, CEP, tel, placa, cartão | `mask` + mesmas do Validation |
| `Flatpickr` | Datepicker, MonthPicker, RangePicker | `mode`, `monthYearOnly`, `clearable`, `mindate`, `maxdate` |
| `Number` | Moeda, decimais, inteiros | `format`, `maxDigits`, `onBlur` |
| `Select` | Dropdown (sync/async) | `async`, `loadOptions`, `altName`, `isMulti`, `options` |
| `Search` | Input + botão de busca | `clickButton`, `mask`, `loading` |
| `CreatableSelect` | Select criável | `loadOptions`, `formatCreateLabel` |
| `Textarea` | Texto longo | `rows` |
| `FileInput` | Upload de arquivo | `multiple` |
| `File` | Upload drag-and-drop | `multiple` |
| `AddonSelect` | Select + input lado a lado | `mask`, `options` |
