(function(){const forms=document.querySelectorAll('.needs-validation');Array.from(forms).forEach(form=>{form.addEventListener('submit',e=>{if(!form.checkValidity()){e.preventDefault();e.stopPropagation()}form.classList.add('was-validated')},false)})})();

document.querySelectorAll('button[data-confirm], a[data-confirm]').forEach(el=>{el.addEventListener('click',e=>{const msg=el.getAttribute('data-confirm')||'Are you sure?';if(!confirm(msg)){e.preventDefault();e.stopPropagation()}})});

window.showToast=function(message,variant='success'){const t=document.createElement('div');t.className=`position-fixed top-0 end-0 m-3 p-3 text-white rounded shadow ${variant==='success'?'bg-success':'bg-danger'}`;t.style.zIndex=1080;t.textContent=message;document.body.appendChild(t);setTimeout(()=>t.remove(),3000)};
