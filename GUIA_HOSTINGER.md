# Guia Completo: Deploy no Hostinger

## 1. PREPARAÇÃO DOS ARQUIVOS

### Arquivos que devem ser enviados:
```
├── ajax/
│   ├── get-chart-data.php
│   └── register-profit.php
├── assets/
│   ├── css/style.css
│   └── js/
│       ├── chart.js
│       └── script.js
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── header.php
├── operations/
│   ├── add-balance.php
│   ├── close-operation.php
│   ├── create-operation.php
│   ├── get-active-operations.php
│   ├── list-operations.php
│   └── view-operation.php
├── index.php
├── login.php
├── logout.php
├── register.php
├── forgot-password.php
├── reset-password.php
└── health-check.php
```

## 2. CONFIGURAÇÃO DO BANCO DE DADOS

### No painel do Hostinger:
1. Acesse **Banco de Dados MySQL**
2. Anote as informações:
   - **Host**: srv1887.hstgr.io
   - **Usuário**: u999216088_registrocripto  
   - **Senha**: Copytrade@2025
   - **Banco**: u999216088_registrocripto

### Verificar se as tabelas existem:
- `usuarios`
- `operacoes`
- `registros_lucro`
- `sessoes`
- `password_resets`
- `adicoes_saldo`

## 3. UPLOAD DOS ARQUIVOS

### Método 1: File Manager do Hostinger
1. Acesse **File Manager** no painel
2. Navegue até `/public_html/`
3. **IMPORTANTE**: Delete todos os arquivos existentes primeiro
4. Faça upload de todos os arquivos mantendo a estrutura de pastas

### Método 2: FTP
```
Host: srv1887.hstgr.io
Usuário: [seu_usuario_hostinger]
Senha: [sua_senha_hostinger]
Porta: 21
```

## 4. CONFIGURAÇÃO DE PERMISSÕES

### Definir permissões corretas:
```
- Pastas: 755
- Arquivos PHP: 644
- config/database.php: 600 (mais seguro)
```

## 5. CONFIGURAÇÃO DO config/database.php

### Verificar se está assim:
```php
<?php
$host = 'srv1887.hstgr.io';
$username = 'u999216088_registrocripto';
$password = 'Copytrade@2025';
$database = 'u999216088_registrocripto';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Erro na conexão com o banco de dados. Tente novamente mais tarde.");
}
?>
```

## 6. ESTRUTURA DE PASTAS NO HOSTINGER

```
public_html/
├── ajax/
├── assets/
│   ├── css/
│   └── js/
├── config/
├── includes/
├── operations/
├── index.php
├── login.php
├── register.php
└── [outros arquivos PHP]
```

## 7. TESTE DA INSTALAÇÃO

### Verificar funcionamento:
1. Acesse: `https://seudominio.com/health-check.php`
   - Deve retornar: `{"status":"healthy",...}`

2. Acesse: `https://seudominio.com/`
   - Deve redirecionar para login

3. Teste o login com:
   - Email: copytradebrasil@gmail.com
   - Senha: 123456789

## 8. CONFIGURAÇÕES ADICIONAIS

### .htaccess (opcional, criar na raiz):
```apache
# Segurança
Options -Indexes
RewriteEngine On

# Redirecionar para HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger arquivos de configuração
<Files "*.log">
    Order Allow,Deny
    Deny from all
</Files>

<Files "config/*">
    Order Allow,Deny
    Deny from all
</Files>
```

## 9. VERIFICAÇÃO FINAL

### Checklist pós-instalação:
- [ ] Site carrega sem erros
- [ ] Login funciona
- [ ] Dashboard mostra dados
- [ ] Gráficos aparecem
- [ ] Operações podem ser criadas
- [ ] Lucros podem ser registrados
- [ ] Banco de dados conecta corretamente

## 10. SOLUÇÃO DE PROBLEMAS

### Erro 500:
- Verificar permissões dos arquivos
- Verificar logs de erro no painel
- Verificar conexão com banco

### Erro de banco:
- Confirmar credenciais em config/database.php
- Verificar se banco está ativo
- Testar conexão via phpMyAdmin

### Páginas em branco:
- Ativar display_errors no PHP
- Verificar logs de erro
- Verificar sintaxe dos arquivos PHP

## 11. MANUTENÇÃO

### Backup regular:
- Backup dos arquivos mensalmente
- Backup do banco semanalmente
- Monitorar logs de erro

### Atualizações:
- Sempre testar em ambiente local primeiro
- Fazer backup antes de atualizações
- Verificar compatibilidade PHP

---

**IMPORTANTE**: Mantenha as credenciais do banco seguras e faça backups regulares!