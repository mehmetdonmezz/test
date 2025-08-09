(function(){
  function isValidTRPhone(value){
    if(!value) return true; // boşsa zorunlu değil
    // Kabul: 05XXXXXXXXX veya +90 5XXXXXXXXX (boşluk/çizgi esnek)
    const digits = value.replace(/[^0-9+]/g,'');
    const re1 = /^05\d{9}$/; // 11 hane, 05 ile
    const re2 = /^\+?90?5\d{9}$/; // +90 5XXXXXXXXX veya 905XXXXXXXXX
    return re1.test(digits) || re2.test(digits);
  }
  function attachValidation(){
    const form = document.querySelector('form[action="panel.php"]');
    if(!form) return;
    const phoneInputs = [
      form.querySelector('input[name="emergency_phone"]'),
      form.querySelector('input[name="doctor_phone"]')
    ].filter(Boolean);
    phoneInputs.forEach(inp => {
      inp.addEventListener('input', () => {
        if(isValidTRPhone(inp.value)){
          inp.classList.remove('is-invalid');
        } else {
          inp.classList.add('is-invalid');
        }
      });
    });
    form.addEventListener('submit', (e) => {
      const invalid = phoneInputs.some(inp => !isValidTRPhone(inp.value));
      if(invalid){
        e.preventDefault();
        alert('Telefon formatı geçersiz. Örnek: 05XXXXXXXXX veya +90 5XXXXXXXXX');
      }
    });
  }
  document.addEventListener('DOMContentLoaded', attachValidation);
})();