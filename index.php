<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pagamento PIX - Checkout Seguro</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
    body { font-family: Arial, sans-serif; background: #0f0f0f; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    button { background: #e50914; border: none; color: white; padding: 14px 25px; font-size: 16px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
    button:hover { background: #bf0811; }
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1000; }
    .modal-content { background: #1c1c1c; padding: 30px; border-radius: 10px; text-align: center; width: 360px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
    
    textarea { width: 100%; margin-top: 10px; padding: 10px; border-radius: 6px; border: 1px solid #444; resize: none; background: #333; color: white; font-family: monospace; box-sizing: border-box; }
    
    .copy { background: #0aa34f; margin-top: 15px; width: 100%; }
    .copy:hover { background: #088641; }
    .close { background: #444; margin-top: 10px; width: 100%; }
    .close:hover { background: #333; }
    
    .loader { border: 4px solid #333; border-top: 4px solid #e50914; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 15px auto; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    
    #qrcode { margin-top: 15px; display: flex; justify-content: center; background: white; padding: 10px; border-radius: 8px; width: 200px; margin-left: auto; margin-right: auto; }
    #statusPagamento { margin-top: 20px; font-size: 14px; color: #aaa; }
</style>
</head>

<body>

<button onclick="abrirModal()">Pagar com PIX</button>

<div id="pixModal" class="modal">
    <div class="modal-content">
        <h2>Pagamento via PIX</h2>
        <div id="pixConteudo">
            <div class="loader"></div>
            <p>Gerando PIX único e seguro...</p>
        </div>
        <button class="close" onclick="fecharModal()">Cancelar</button>
    </div>
</div>

<script>
let pollingInterval = null;

function abrirModal(){
    document.getElementById("pixModal").style.display = "flex";
    gerarPix();
}

function fecharModal(){
    document.getElementById("pixModal").style.display = "none";
    if (pollingInterval) clearInterval(pollingInterval); 
    
    // Reseta o conteúdo para a próxima vez
    document.getElementById("pixConteudo").innerHTML = `
        <div class="loader"></div>
        <p>Gerando PIX único e seguro...</p>
    `;
}

function gerarPix(){
    fetch("criar_pix.php", { method: "POST" })
    .then(res => res.json())
    .then(data => {
        if (!data.qr_code) {
            console.error("Retorno da API:", data);
            throw new Error("A API não retornou o qr_code");
        }

        let codigoPix = data.qr_code; 
        
        document.getElementById("pixConteudo").innerHTML = `
            <p style="font-size: 14px; color: #ccc;">Escaneie o QR Code abaixo:</p>
            <div id="qrcode"></div>
            <p style="font-size: 14px; color: #ccc; margin-top: 15px;">Ou copie o código (Pix Copia e Cola):</p>
            <textarea id="pixCode" rows="3" readonly>${codigoPix}</textarea>
            <button class="copy" onclick="copiarPix()">Copiar Código PIX</button>
            <div id="statusPagamento">
                <span class="loader" style="width:12px; height:12px; display:inline-block; border-width:2px; vertical-align:middle; margin:0 5px 0 0;"></span> 
                Aguardando pagamento...
            </div>
        `;

        // CORREÇÃO: Adicionamos o 'correctLevel' para aceitar textos gigantes do PIX
        new QRCode(document.getElementById("qrcode"), {
            text: codigoPix,
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.L
        });

        let identificadorDaTransacao = data.transaction_id || data.id; 
        
        if (identificadorDaTransacao) {
            iniciarPollingSeguro(identificadorDaTransacao);
        }
    })
    .catch((erro) => {
        console.error("Erro capturado:", erro);
        document.getElementById("pixConteudo").innerHTML = "<p style='color:#e50914; font-weight:bold;'>Erro ao comunicar com o servidor. Tente novamente.</p>";
    });
}

function copiarPix(){
    let pix = document.getElementById("pixCode");
    pix.select();
    navigator.clipboard.writeText(pix.value);
    
    let btnCopy = document.querySelector('.copy');
    let textoOriginal = btnCopy.innerText;
    btnCopy.innerText = "Copiado!";
    btnCopy.style.background = "#065f2d";
    
    setTimeout(() => {
        btnCopy.innerText = textoOriginal;
        btnCopy.style.background = "#0aa34f";
    }, 2000);
}

function iniciarPollingSeguro(hash) {
    if (pollingInterval) clearInterval(pollingInterval);
    
    // Verifica de 3 em 3 segundos
    pollingInterval = setInterval(() => {
        fetch(`check_status.php?hash=${hash}`)
        .then(res => res.json())
        .then(statusData => {
            if (statusData.status === 'paid') {
                clearInterval(pollingInterval);
                document.getElementById("statusPagamento").innerHTML = "<strong style='color:#0aa34f; font-size: 16px;'>Pagamento Confirmado! Redirecionando...</strong>";
                redirecionarParaUpsell(statusData.redirect_url);
            }
        })
        .catch(err => console.error("Erro na verificação:", err));
    }, 3000);
}

function redirecionarParaUpsell(urlSecretaBackend) {
    const utms = window.location.search; 
    let urlFinal = urlSecretaBackend;
    
    if (utms) {
        urlFinal += urlFinal.includes('?') ? utms.replace('?', '&') : utms;
    }
    
    setTimeout(() => {
        window.location.href = urlFinal;
    }, 1500); 
}
</script>

</body>
</html>