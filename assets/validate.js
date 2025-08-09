(function(){
  function isValidTRPhone(value){
    if(!value) return true; // boşsa zorunlu değil
    const digits = value.replace(/[^0-9+]/g,'');
    const re1 = /^05\d{9}$/;
    const re2 = /^\+?90?5\d{9}$/;
    return re1.test(digits) || re2.test(digits);
  }
  function maskTRPhone(value){
    const digits = value.replace(/\D/g,'');
    // +90 5XXXXXXXXX veya 05XXXXXXXXX formatlayalım
    if(digits.startsWith('90')){
      const d = digits.slice(0,12); // 90 + 10 hane
      // 90 5xx xxx xx xx
      const p = d.padEnd(12,'');
      return `+${p.slice(0,2)} ${p.slice(2,3)}${p.slice(3,5)} ${p.slice(5,8)} ${p.slice(8,10)} ${p.slice(10,12)}`.trim();
    } else if(digits.startsWith('5')){
      const d = digits.slice(0,10);
      // 5xx xxx xx xx
      return `0${d.slice(0,3)} ${d.slice(3,6)} ${d.slice(6,8)} ${d.slice(8,10)}`.trim();
    } else if(digits.startsWith('0')){
      const d = digits.slice(0,11);
      // 0 5xx xxx xx xx
      return `${d.slice(0,1)}${d.slice(1,4)} ${d.slice(4,7)} ${d.slice(7,9)} ${d.slice(9,11)}`.trim();
    }
    return value; // bilinmiyorsa dokunma
  }
  function normalizeAddress(value){
    if(!value) return '';
    // Trim, fazla boşlukları ve ardışık boş satırları azalt
    let v = value.replace(/\r\n/g,'\n');
    v = v.split('\n').map(line=>line.trim().replace(/\s+/g,' ')).filter((line,i,arr)=>!(line==='' && arr[i-1]==='')).join('\n');
    return v;
  }
  function attachValidation(){
    const form = document.querySelector('form[action="panel.php"]');
    if(!form) return;

    // Unsaved changes guard
    let dirty = false;
    const markDirty = () => { dirty = true; };
    form.querySelectorAll('input, textarea, select').forEach(el=>{
      el.addEventListener('input', markDirty);
      el.addEventListener('change', markDirty);
    });
    window.addEventListener('beforeunload', (e)=>{
      if(dirty){
        e.preventDefault();
        e.returnValue = '';
      }
    });
    // Tab değişimi uyarısı
    document.querySelectorAll('#infoTabs .nav-link').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        if(dirty && !confirm('Kaydedilmemiş değişiklikler var. Sekme değiştirmek istediğinizden emin misiniz?')){
          e.preventDefault();
        }
      });
    });
    form.addEventListener('submit', ()=>{ dirty = false; });

    // Phone inputs mask + validate
    const phoneInputs = [
      form.querySelector('input[name="emergency_phone"]'),
      form.querySelector('input[name="doctor_phone"]')
    ].filter(Boolean);
    phoneInputs.forEach(inp => {
      inp.addEventListener('input', () => {
        const pos = inp.selectionStart;
        const masked = maskTRPhone(inp.value);
        inp.value = masked;
        if(isValidTRPhone(inp.value)){
          inp.classList.remove('is-invalid');
        } else {
          inp.classList.add('is-invalid');
        }
      });
      inp.addEventListener('blur', ()=>{
        if(!isValidTRPhone(inp.value)) inp.classList.add('is-invalid');
      });
    });

    // Address normalize on blur
    const adr = form.querySelector('textarea[name="address"]');
    if(adr){
      adr.addEventListener('blur', ()=>{
        adr.value = normalizeAddress(adr.value);
      });
    }
  }
  document.addEventListener('DOMContentLoaded', attachValidation);
})();