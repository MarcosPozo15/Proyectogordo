console.log('app.js cargado');

// gráfica por horas
(() => {
  const d = window.DATA_HORARIA || {};
  const etiquetas = d.etiquetas || [];
  const valores   = d.valores   || [];
  const el = document.getElementById('grafica');
  if(!el || !etiquetas.length) return;

  const colores = valores.map(p =>
    p < 0.10 ? 'rgba(0,170,0,.7)' :
    p <= 0.12 ? 'rgba(255,165,0,.9)' :
                'rgba(230,40,40,.8)'
  );

  new Chart(el.getContext('2d'), {
    type:'bar',
    data:{ labels: etiquetas, datasets:[{ label:'Precio €/kWh por hora', data: valores, backgroundColor: colores, borderColor: colores, borderWidth:1 }]},
    options:{ responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
  });
})();

// gráfica mensual
(() => {
  const d = window.DATA_MENSUAL || {};
  const etiquetas = d.etiquetas || [];
  const valores   = d.valores   || [];
  const el = document.getElementById('graficaMensual');
  if(!el || !etiquetas.length) return;

  new Chart(el.getContext('2d'), {
    type:'line',
    data:{ labels: etiquetas, datasets:[{ label:'PVPC', data: valores, fill:false, tension:.25, borderColor:'rgba(255,165,0,1)', pointRadius:3, pointBackgroundColor:'rgba(255,165,0,1)',
      segment:{ borderDash: ctx => (ctx.p1DataIndex === valores.length - 1 ? [6,6] : undefined) } }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ labels:{ usePointStyle:true } } } }
  });
})();

