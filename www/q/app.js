const questions=[
{q:'Wird generative KI in Ihrem Unternehmen heute bereits genutzt?',type:'single',o:[['✦','Ja, regelmäßig'],['◐','Teilweise / punktuell'],['○','Noch nicht']]},
{q:'In welchen Bereichen wird KI eingesetzt?',type:'multi',o:[['▦','Office'],['⌘','IT'],['↗','Vertrieb'],['◌','Kundenservice'],['◇','Entwicklung'],['⌁','Analyse'],['＋','Weitere']]},
{q:'Erfolgt diese Nutzung überwiegend offiziell freigegeben oder auch eigenständig durch Mitarbeitende?',type:'single',o:[['✓','Überwiegend freigegeben'],['⇄','Beides'],['↯','Überwiegend eigenständig'],['?','Kann ich nicht beurteilen']]},
{q:'Gibt es bereits KI-Anwendungen, die regelmäßig in Geschäftsprozessen eingesetzt werden?',type:'single',o:[['✓','Ja'],['◐','Vereinzelt'],['○','Nein']]},
{q:'Wie wichtig ist ein stärkerer KI-Einsatz für Ihr Unternehmen in den nächsten 12 Monaten?',type:'scale',o:[['1','Unwichtig'],['2','Eher unwichtig'],['3','Mittel'],['4','Wichtig'],['5','Sehr wichtig']]},
{q:'Was sind aktuell die größten Hindernisse für einen breiteren KI-Einsatz?',type:'multi',o:[['⌾','Datenschutz'],['⬡','Informationssicherheit'],['⇄','Integration'],['€','Kosten'],['◇','Qualität'],['◎','Know-how'],['§','Regulierung'],['◌','Akzeptanz'],['＋','Weitere']]},
{q:'Gibt es Daten oder Prozesse, bei denen Sie KI gerne einsetzen würden, es aus Sicherheits- oder Datenschutzgründen aber nicht tun?',type:'single',o:[['✓','Ja'],['◐','Vielleicht / unklar'],['○','Nein']]},
{q:'Wie sicher können Sie heute beurteilen, welche Unternehmensdaten an welche KI-Dienste übergeben werden dürfen?',type:'scale',o:[['1','Sehr unsicher'],['2','Eher unsicher'],['3','Teils / teils'],['4','Eher sicher'],['5','Sehr sicher']]},
{q:'Wie groß ist die Sorge, durch KI die Kontrolle über Daten oder Geschäftsprozesse zu verlieren?',type:'scale',o:[['1','Keine Sorge'],['2','Gering'],['3','Mittel'],['4','Groß'],['5','Sehr groß']]},
{q:'Was müsste sich ändern, damit Sie KI deutlich breiter einsetzen würden?',type:'text',placeholder:'Ein kurzer Gedanke reicht …'},
{q:'Wie wichtig wäre es Ihnen, unterschiedliche KI-Modelle je nach Aufgabe und Schutzbedarf einsetzen zu können?',type:'scale',o:[['1','Unwichtig'],['2','Eher unwichtig'],['3','Mittel'],['4','Wichtig'],['5','Sehr wichtig']]},
{q:'Wie wichtig ist Ihnen die Möglichkeit, sensible Aufgaben vollständig innerhalb Ihrer eigenen Infrastruktur auszuführen?',type:'scale',o:[['1','Unwichtig'],['2','Eher unwichtig'],['3','Mittel'],['4','Wichtig'],['5','Sehr wichtig']]},
{q:'Wie wichtig wäre eine zentrale Kontrolle darüber, welche Daten für welchen KI-Dienst verwendet werden dürfen?',type:'scale',o:[['1','Unwichtig'],['2','Eher unwichtig'],['3','Mittel'],['4','Wichtig'],['5','Sehr wichtig']]},
{q:'Wie wichtig ist die Nachvollziehbarkeit dessen, was eine KI mit Unternehmensdaten getan hat?',type:'scale',o:[['1','Unwichtig'],['2','Eher unwichtig'],['3','Mittel'],['4','Wichtig'],['5','Sehr wichtig']]},
{q:'Würden Sie KI mehr operative Aufgaben übertragen, wenn Berechtigungen, Datenzugriffe und Ergebnisse kontrollierbar wären?',type:'single',o:[['✓','Ja'],['◐','Wahrscheinlich'],['?','Unentschieden'],['○','Nein']]},
{q:'Bei welchen Aufgaben erwarten Sie aktuell den größten wirtschaftlichen Nutzen durch KI?',type:'multi',o:[['⚙','Automation'],['⌁','Analyse'],['◌','Service'],['↗','Vertrieb'],['✎','Dokumentation'],['◇','Entwicklung'],['＋','Weitere']]},
{q:'Welche wiederkehrenden Tätigkeiten würden Sie am liebsten als Erstes automatisieren?',type:'text',placeholder:'Kurze Stichworte genügen …'},
{q:'Wäre für Sie eine Lösung interessanter, die vorhandene Systeme verbindet, statt weitere Insellösungen einzuführen?',type:'single',o:[['✓','Ja'],['◐','Kommt darauf an'],['○','Nein']]},
{q:'Was wäre für Sie der wichtigste messbare Erfolg eines KI-Projekts?',type:'single',o:[['◷','Zeitersparnis'],['€','Kosten'],['◇','Qualität'],['⚡','Geschwindigkeit'],['◎','Transparenz'],['☺','Mitarbeiterentlastung'],['＋','Anderes']]},
{q:'Wenn ein sicherer produktiver KI-Einsatz möglich wäre: Wo würden Sie morgen anfangen?',type:'text',placeholder:'Was wäre Ihr erster Anwendungsfall?',maxLength:4000,counterFrom:3000}
];

function renderQuestion(item,index){
  const number=index+1;
  const wrap=document.createElement('div');
  wrap.className='question';
  wrap.dataset.question=number;
  wrap.dataset.type=item.type;
  const title=document.createElement('p');
  title.className='question-title';
  title.textContent=`${number}. ${item.q}`;
  wrap.append(title);
  if(item.type==='text'){
    const area=document.createElement('textarea');
    area.className='text-answer';area.name=`q${number}`;area.rows=2;area.placeholder=item.placeholder||'';area.setAttribute('aria-label',item.q);
    if(item.maxLength){
      area.maxLength=item.maxLength;
      const counter=document.createElement('div');
      counter.className='text-counter';
      counter.hidden=true;
      counter.setAttribute('aria-live','polite');
      counter.style.marginTop='.65rem';
      counter.style.textAlign='right';
      counter.style.color='var(--muted)';
      counter.style.fontSize='.9rem';
      const updateCounter=()=>{
        const length=area.value.length;
        counter.hidden=length<(item.counterFrom||0);
        counter.textContent=`${length.toLocaleString('de-DE')} / ${item.maxLength.toLocaleString('de-DE')} Zeichen`;
      };
      area.addEventListener('input',updateCounter);
      wrap.append(area,counter);
      return wrap;
    }
    wrap.append(area);return wrap;
  }
  const answers=document.createElement('div');answers.className='answers';
  item.o.forEach(([icon,label],i)=>{
    const cell=document.createElement('div');cell.className='answer';
    const input=document.createElement('input');input.type=item.type==='multi'?'checkbox':'radio';input.name=item.type==='multi'?`q${number}[]`:`q${number}`;input.id=`q${number}-${i}`;input.value=label;
    const lab=document.createElement('label');lab.htmlFor=input.id;lab.innerHTML=`<span class="answer-icon" aria-hidden="true">${icon}</span><span>${label}</span>`;
    cell.append(input,lab);answers.append(cell);
  });
  wrap.append(answers);return wrap;
}

questions.forEach((q,i)=>document.querySelector(`#questions-${Math.floor(i/5)+1}`).append(renderQuestion(q,i)));

function scrollToNextQuestion(currentQuestion){
  const questionNumber=Number(currentQuestion.dataset.question);
  const next=document.querySelector(`.question[data-question="${questionNumber+1}"]`);
  const target=next||document.querySelector('#finish');
  if(!target)return;
  window.setTimeout(()=>target.scrollIntoView({behavior:'smooth',block:next?'center':'start'}),120);
}

document.querySelectorAll('.question[data-type="single"] input[type="radio"], .question[data-type="scale"] input[type="radio"]').forEach(input=>{
  input.addEventListener('change',()=>scrollToNextQuestion(input.closest('.question')));
});

document.querySelectorAll('.question[data-question="10"] .text-answer, .question[data-question="17"] .text-answer').forEach(area=>{
  area.addEventListener('keydown',event=>{
    if(event.key!=='Enter'||event.shiftKey||event.isComposing)return;
    event.preventDefault();
    scrollToNextQuestion(area.closest('.question'));
  });
});

document.querySelectorAll('[data-scroll]').forEach(btn=>btn.addEventListener('click',()=>document.querySelector(btn.dataset.scroll)?.scrollIntoView({behavior:'smooth'})));

window.addEventListener('scroll',()=>{
  const max=document.documentElement.scrollHeight-innerHeight;
  document.querySelector('#progress-bar').style.width=`${max?scrollY/max*100:0}%`;
},{passive:true});

const questionObserver=new IntersectionObserver(entries=>{
  entries.forEach(entry=>entry.target.classList.toggle('is-active',entry.isIntersecting));
},{rootMargin:'-28% 0px -28% 0px',threshold:.15});
document.querySelectorAll('.question').forEach(q=>questionObserver.observe(q));

const chapterLinks=[...document.querySelectorAll('[data-chapter]')];
const chapterObserver=new IntersectionObserver(entries=>{
  const visible=entries.filter(e=>e.isIntersecting).sort((a,b)=>b.intersectionRatio-a.intersectionRatio)[0];
  if(!visible)return;
  const chapter=visible.target.dataset.package;
  chapterLinks.forEach(link=>link.classList.toggle('is-active',link.dataset.chapter===chapter));
},{rootMargin:'-35% 0px -35% 0px',threshold:[.05,.2,.5]});
document.querySelectorAll('[data-package]').forEach(section=>chapterObserver.observe(section));

const questionnaire=document.querySelector('#questionnaire');
const timeDialog=document.querySelector('#time-dialog');
const startQuestionnaire=document.querySelector('#start-questionnaire');
let questionnaireTimer=null;
let questionnaireSubmitted=false;
let timeReminderShown=false;

function startTwoMinuteTimer(){
  if(questionnaireTimer||questionnaireSubmitted||timeReminderShown)return;
  questionnaireTimer=window.setTimeout(()=>{
    questionnaireTimer=null;
    if(questionnaireSubmitted||timeReminderShown)return;
    timeReminderShown=true;
    if(typeof timeDialog.showModal==='function')timeDialog.showModal();
    else timeDialog.setAttribute('open','');
  },120000);
}

startQuestionnaire.addEventListener('click',startTwoMinuteTimer);

document.querySelector('#time-dialog-continue').addEventListener('click',()=>{
  timeDialog.close();
});

document.querySelector('#time-dialog-submit').addEventListener('click',()=>{
  timeDialog.close();
  questionnaire.requestSubmit();
});

questionnaire.addEventListener('submit',e=>{
  e.preventDefault();
  questionnaireSubmitted=true;
  if(questionnaireTimer){window.clearTimeout(questionnaireTimer);questionnaireTimer=null;}
  if(timeDialog.open)timeDialog.close();
  const data=new FormData(questionnaire);
  const payload={questionnaireVersion:'research-20-v1',submittedAt:new Date().toISOString(),answers:{}};
  for(const [key,value] of data.entries()){
    const clean=key.replace('[]','');
    if(payload.answers[clean]===undefined)payload.answers[clean]=value;
    else if(Array.isArray(payload.answers[clean]))payload.answers[clean].push(value);
    else payload.answers[clean]=[payload.answers[clean],value];
  }
  console.info('Questionnaire payload ready for API:',payload);
  document.querySelector('#submit-state').textContent='Antworten sind für die Übermittlung vorbereitet. Die API-Anbindung folgt im nächsten Schritt.';
  const contact=document.querySelector('#contact');contact.hidden=false;
  setTimeout(()=>contact.scrollIntoView({behavior:'smooth'}),250);
});

document.querySelector('#contact-form').addEventListener('submit',e=>{
  e.preventDefault();
  const fd=new FormData(e.currentTarget);
  const message=(fd.get('message')||'').trim();
  const email=(fd.get('email')||'').trim();
  if(!message&&!email){document.querySelector('#contact-state').textContent='Sie können das Formular auch einfach leer lassen.';return;}
  console.info('Independent contact payload ready for API:',{message,email});
  document.querySelector('#contact-state').textContent='Nachricht ist für die separate Übermittlung vorbereitet. Die API-Anbindung folgt im nächsten Schritt.';
});