{{--
    Voz común de la dirección (23/11/2026): dictar (Web Speech API del navegador) y leer en voz alta (speechSynthesis).
    Uso: x-data="veliaDictado()" y un <textarea x-ref="destino">. Si el navegador no tiene reconocimiento de voz, el botón no aparece:
    siempre se puede escribir (no depender solo de la voz, WCAG). El navegador envía la voz a Google o Apple para pasarla a texto.
--}}
<script>
    window.veliaDictado = function () {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        return {
            soportado: !!SR, grabando: false, error: '', rec: null, base: '',
            alternar() { this.grabando ? this.parar() : this.empezar(); },
            empezar() {
                if (!SR) return;
                this.error = ''; this.base = this.$refs.destino.value ? this.$refs.destino.value.trim() + ' ' : '';
                this.rec = new SR(); this.rec.lang = 'es-ES'; this.rec.continuous = true; this.rec.interimResults = true;
                let final = '';
                this.rec.onresult = (e) => {
                    let parcial = '';
                    for (let i = e.resultIndex; i < e.results.length; i++) {
                        if (e.results[i].isFinal) final += e.results[i][0].transcript.trim() + '. ';
                        else parcial += e.results[i][0].transcript;
                    }
                    this.$refs.destino.value = (this.base + final + parcial).trim();
                    this.$refs.destino.dispatchEvent(new Event('input', { bubbles: true }));
                };
                this.rec.onerror = (e) => { this.grabando = false; this.error = e.error === 'not-allowed' ? 'El navegador no deja usar el micrófono. Revisa el permiso o escribe.' : 'No se ha podido escuchar. Prueba otra vez o escribe.'; };
                this.rec.onend = () => { this.grabando = false; };
                this.rec.start(); this.grabando = true;
            },
            parar() { this.rec && this.rec.stop(); this.grabando = false; },
        };
    };
    window.veliaLeer = function (texto) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(texto); u.lang = 'es-ES'; u.rate = 0.95;
        window.speechSynthesis.speak(u);
    };
</script>
