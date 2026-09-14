import React, {useCallback, useEffect, useMemo, useRef, useState} from 'https://esm.sh/react@18.3.1';
import {createRoot} from 'https://esm.sh/react-dom@18.3.1/client';
import {
  ReactFlow, ReactFlowProvider, Background, Controls, MiniMap, Handle, Position,
  useViewport
} from 'https://esm.sh/@xyflow/react@12.11.6?deps=react@18.3.1,react-dom@18.3.1';

const h = React.createElement;
const API = './api/document.php';
const uid = prefix => `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2,6)}`;

function ModuleNode({data}) {
  const {zoom} = useViewport();
  const showInterfaces = zoom >= .72;
  const showComponents = zoom >= 1.12;
  const interfaces = data.interfaces || [];
  const components = data.components || [];
  return h('div', {className: 'node-card'},
    h('div', {className: 'node-head'},
      h('div', {className: 'node-title'}, data.name || 'MODULE'),
      h('div', {className: 'node-meta'}, data.status || 'planned')
    ),
    (showInterfaces || showComponents) && h('div', {className: 'node-body'},
      showComponents && components.length ? h('div', {className: 'component-list'},
        h('div', {className: 'section-label'}, 'Components'),
        ...components.map(c => h('div', {className:'component-chip', key:c.id}, c.name || c.id))
      ) : null,
      showInterfaces ? h('div', {className:'ports'},
        h('div', {className:'section-label'}, 'Interfaces'),
        ...interfaces.map(port => {
          const out = port.direction === 'out';
          return h('div', {className:'port-row', key:port.id},
            !out && h(Handle, {type:'target', position:Position.Left, id:port.id}),
            h('span', {className:'port-direction'}, out ? 'OUT' : 'IN'),
            h('span', {className:'port-name'}, port.name || port.id),
            out && h(Handle, {type:'source', position:Position.Right, id:port.id})
          );
        })
      ) : null
    )
  );
}

function ProcessStepNode({data}) {
  return h('div', {className:`process-node ${data.external ? 'external' : ''}`},
    h('div', {className:'process-actor'}, data.actorLabel),
    h('strong', null, data.name),
    data.moduleName ? h('span', {className:'process-module'}, data.moduleName) : null,
    data.interfaceName ? h('span', {className:'process-interface'}, data.interfaceName) : null,
    h(Handle, {type:'target', position:Position.Top, id:'in'}),
    h(Handle, {type:'source', position:Position.Bottom, id:'out'})
  );
}

const nodeTypes = {module: ModuleNode, processStep: ProcessStepNode};

function consistencyIssues(doc) {
  const issues = [];
  const modules = new Map((doc.modules || []).map(m => [m.id, m]));
  const processes = new Map((doc.processes || []).map(p => [p.id, p]));
  const iface = (moduleId, interfaceId) => {
    if (!interfaceId) return true;
    const m = modules.get(moduleId);
    return !!m && (m.interfaces || []).some(i => i.id === interfaceId);
  };
  for (const c of doc.connections || []) {
    if (!modules.has(c.source?.moduleId)) issues.push({level:'error', text:`Connection ${c.id}: source module fehlt.`});
    if (!modules.has(c.target?.moduleId)) issues.push({level:'error', text:`Connection ${c.id}: target module fehlt.`});
    if (!iface(c.source?.moduleId, c.source?.interfaceId)) issues.push({level:'error', text:`Connection ${c.id}: source interface fehlt.`});
    if (!iface(c.target?.moduleId, c.target?.interfaceId)) issues.push({level:'error', text:`Connection ${c.id}: target interface fehlt.`});
  }
  const usedPairs = new Set();
  for (const p of doc.processes || []) {
    if (!(p.steps || []).length) issues.push({level:'warn', text:`Process ${p.name}: keine Schritte.`});
    for (const s of p.steps || []) {
      if (s.moduleId && !modules.has(s.moduleId)) issues.push({level:'error', text:`${p.name} / ${s.name}: Modul ${s.moduleId} fehlt.`});
      if (s.moduleId && !iface(s.moduleId, s.interfaceId)) issues.push({level:'error', text:`${p.name} / ${s.name}: Interface ${s.interfaceId} fehlt.`});
      if (s.targetModuleId && !modules.has(s.targetModuleId)) issues.push({level:'error', text:`${p.name} / ${s.name}: Zielmodul ${s.targetModuleId} fehlt.`});
      if (s.targetModuleId && !iface(s.targetModuleId, s.targetInterfaceId)) issues.push({level:'error', text:`${p.name} / ${s.name}: Zielinterface ${s.targetInterfaceId} fehlt.`});
      if (s.moduleId && s.targetModuleId) usedPairs.add(`${s.moduleId}>${s.targetModuleId}`);
    }
  }
  for (const pair of usedPairs) {
    const [source, target] = pair.split('>');
    const exists = (doc.connections || []).some(c => c.source?.moduleId === source && c.target?.moduleId === target);
    if (!exists) issues.push({level:'warn', text:`Process nutzt ${source} → ${target}, aber Architektur hat keine Verbindung.`});
  }
  for (const r of doc.roadmap || []) {
    if (r.targetType === 'module' && !modules.has(r.targetId)) issues.push({level:'error', text:`Roadmap ${r.name}: Modulziel fehlt.`});
    if (r.targetType === 'process' && !processes.has(r.targetId)) issues.push({level:'error', text:`Roadmap ${r.name}: Prozessziel fehlt.`});
  }
  const plannedModules = new Set((doc.roadmap || []).filter(r => r.targetType === 'module').map(r => r.targetId));
  const processModules = new Set((doc.processes || []).flatMap(p => (p.steps || []).flatMap(s => [s.moduleId, s.targetModuleId]).filter(Boolean)));
  for (const id of processModules) if (!plannedModules.has(id)) issues.push({level:'info', text:`Modul ${modules.get(id)?.name || id} wird in Prozessen genutzt, hat aber keinen Roadmap-Eintrag.`});
  return issues;
}

function ArchitectureView({doc, setDoc, select}) {
  const positions = doc.views?.architecture?.positions || {};
  const nodes = useMemo(() => (doc.modules || []).map((m, i) => ({
    id:m.id, type:'module', position:positions[m.id] || {x:100 + (i%3)*340, y:120 + Math.floor(i/3)*280}, data:m
  })), [doc.modules, positions]);
  const edges = useMemo(() => (doc.connections || []).map(c => ({
    id:c.id, source:c.source?.moduleId, target:c.target?.moduleId,
    sourceHandle:c.source?.interfaceId || null, targetHandle:c.target?.interfaceId || null,
    label:c.label || '', type:'smoothstep'
  })), [doc.connections]);

  const onNodesChange = changes => {
    let nextPos = {...positions};
    let changed = false;
    for (const c of changes) {
      if (c.type === 'position' && c.position) { nextPos[c.id] = c.position; changed = true; }
    }
    if (changed) setDoc(d => ({...d, views:{...d.views, architecture:{...(d.views?.architecture || {}), positions:nextPos}}}));
  };
  const onConnect = connection => setDoc(d => ({...d, connections:[...(d.connections || []), {
    id:uid('conn'),
    source:{moduleId:connection.source, interfaceId:connection.sourceHandle || null},
    target:{moduleId:connection.target, interfaceId:connection.targetHandle || null},
    label:'', description:''
  }]}));

  return h('div', {className:'canvas'},
    h(ReactFlow, {nodes, edges, nodeTypes, onNodesChange, onConnect,
      onNodeClick:(_,n)=>select({type:'module', id:n.id}),
      onEdgeClick:(_,e)=>select({type:'connection', id:e.id}),
      onPaneClick:()=>select(null), fitView:true, minZoom:.2, maxZoom:2.2, deleteKeyCode:null},
      h(Background,{gap:20,size:1}), h(MiniMap,{pannable:true,zoomable:true}), h(Controls)
    ),
    h('div',{className:'zoom-note'},'Zoom: Module → Interfaces → Components')
  );
}

function ProcessesView({doc, setDoc, select, selectedProcessId, setSelectedProcessId}) {
  const process = (doc.processes || []).find(p => p.id === selectedProcessId) || (doc.processes || [])[0];
  useEffect(() => { if (!selectedProcessId && process) setSelectedProcessId(process.id); }, [selectedProcessId, process?.id]);
  const moduleMap = useMemo(() => new Map((doc.modules || []).map(m => [m.id,m])), [doc.modules]);
  const nodes = useMemo(() => (process?.steps || []).map((s,i) => {
    const m = moduleMap.get(s.moduleId);
    const intf = m?.interfaces?.find(x => x.id === s.interfaceId);
    return {id:s.id, type:'processStep', position:{x:180 + (i%3)*330, y:90 + Math.floor(i/3)*220}, data:{
      name:s.name, actorLabel:s.actor || (s.moduleId ? 'MODULE':'HUMAN'), moduleName:m?.name || (s.moduleId || ''), interfaceName:intf?.name || '', external:!s.moduleId
    }};
  }), [process, moduleMap]);
  const edges = useMemo(() => nodes.slice(0,-1).map((n,i) => ({id:`flow-${n.id}`, source:n.id, sourceHandle:'out', target:nodes[i+1].id, targetHandle:'in', type:'smoothstep'})), [nodes]);

  return h('div',{className:'view-with-list'},
    h('aside',{className:'left-list'},
      h('div',{className:'list-head'}, h('strong',null,'Processes'), h('button',{className:'icon-btn',onClick:()=>{
        const id=uid('process');
        setDoc(d=>({...d,processes:[...(d.processes||[]),{id,name:'Neuer Prozess',purpose:'',preconditions:[],expectedResult:'',steps:[]}]}));
        setSelectedProcessId(id); select({type:'process',id});
      }},'+')),
      ...(doc.processes || []).map(p => h('button',{key:p.id,className:`list-item ${p.id===process?.id?'active':''}`,onClick:()=>{setSelectedProcessId(p.id);select({type:'process',id:p.id});}},p.name))
    ),
    h('section',{className:'canvas'}, process ? h(ReactFlow,{nodes,edges,nodeTypes,fitView:true,minZoom:.3,maxZoom:1.8,deleteKeyCode:null,
      onNodeClick:(_,n)=>select({type:'step',processId:process.id,id:n.id}), onPaneClick:()=>select({type:'process',id:process.id})},
      h(Background,{gap:20,size:1}),h(Controls)
    ) : h('div',{className:'empty-view'},'Noch kein Prozess.'))
  );
}

function RoadmapView({doc, select}) {
  const items = doc.roadmap || [];
  const dates = items.flatMap(i => [new Date(i.start), new Date(i.end)]).filter(d => !Number.isNaN(+d));
  const min = dates.length ? new Date(Math.min(...dates)) : new Date();
  const max = dates.length ? new Date(Math.max(...dates)) : new Date(+min + 30*86400000);
  const total = Math.max(1, Math.ceil((max-min)/86400000)+1);
  const months=[];
  let cursor=new Date(min.getFullYear(),min.getMonth(),1);
  while(cursor<=max){months.push(new Date(cursor));cursor=new Date(cursor.getFullYear(),cursor.getMonth()+1,1);}
  return h('div',{className:'gantt'},
    h('div',{className:'gantt-head'},
      h('div',{className:'gantt-title'},'Roadmap'),
      h('div',{className:'gantt-axis'},...months.map(m=>h('span',{key:+m},m.toLocaleDateString('de-DE',{month:'short',year:'2-digit'}))))
    ),
    ...items.map(item=>{
      const s=new Date(item.start), e=new Date(item.end);
      const left=Math.max(0,((s-min)/86400000)/total*100);
      const width=Math.max(1,(((e-s)/86400000)+1)/total*100);
      return h('div',{className:'gantt-row',key:item.id,onClick:()=>select({type:'roadmap',id:item.id})},
        h('div',{className:'gantt-label'},h('strong',null,item.name),h('span',null,`${item.group||''} · ${item.status||'planned'}`)),
        h('div',{className:'gantt-track'},h('div',{className:`gantt-bar ${item.status||''}`,style:{left:`${left}%`,width:`${width}%`}},`${item.start} → ${item.end}`))
      );
    })
  );
}

function Inspector({doc, setDoc, selection}) {
  const moduleOptions = (doc.modules || []);
  const patchModule = (id, patch) => setDoc(d=>({...d,modules:d.modules.map(m=>m.id===id?{...m,...patch}:m)}));
  const patchProcess = (id, patch) => setDoc(d=>({...d,processes:d.processes.map(p=>p.id===id?{...p,...patch}:p)}));
  if (!selection) return h('div',{className:'panel-body'},h('div',{className:'empty'},'Objekt auswählen, um Details zu bearbeiten.'));

  if (selection.type === 'module') {
    const m=doc.modules.find(x=>x.id===selection.id); if(!m)return null;
    const patchInterface=(idx,key,val)=>patchModule(m.id,{interfaces:m.interfaces.map((x,i)=>i===idx?{...x,[key]:val}:x)});
    const patchComponent=(idx,key,val)=>patchModule(m.id,{components:m.components.map((x,i)=>i===idx?{...x,[key]:val}:x)});
    return h('div',{className:'panel-body'},
      h('h2',null,'Module'), field('Name',m.name,v=>patchModule(m.id,{name:v})), area('Beschreibung',m.description,v=>patchModule(m.id,{description:v})),
      selectField('Status',m.status,['planned','active','blocked','done'],v=>patchModule(m.id,{status:v})),
      h('h2',null,'Components'),...(m.components||[]).map((c,i)=>h('div',{className:'editor-card',key:c.id},field('Name',c.name,v=>patchComponent(i,'name',v)),area('Beschreibung',c.description,v=>patchComponent(i,'description',v)),h('button',{className:'btn danger',onClick:()=>patchModule(m.id,{components:m.components.filter((_,x)=>x!==i)})},'Löschen'))),
      h('button',{className:'btn',onClick:()=>patchModule(m.id,{components:[...(m.components||[]),{id:uid('component'),name:'Component',description:''}]})},'+ Component'),
      h('h2',null,'Interfaces'),...(m.interfaces||[]).map((p,i)=>h('div',{className:'editor-card',key:p.id},field('Name',p.name,v=>patchInterface(i,'name',v)),selectField('Richtung',p.direction,['in','out'],v=>patchInterface(i,'direction',v)),area('Beschreibung',p.description,v=>patchInterface(i,'description',v)),h('button',{className:'btn danger',onClick:()=>patchModule(m.id,{interfaces:m.interfaces.filter((_,x)=>x!==i)})},'Löschen'))),
      h('button',{className:'btn',onClick:()=>patchModule(m.id,{interfaces:[...(m.interfaces||[]),{id:uid('interface'),name:'Interface',direction:'in',description:''}]})},'+ Interface')
    );
  }

  if (selection.type === 'connection') {
    const c=doc.connections.find(x=>x.id===selection.id); if(!c)return null;
    return h('div',{className:'panel-body'},h('h2',null,'Connection'),field('Label',c.label,v=>setDoc(d=>({...d,connections:d.connections.map(x=>x.id===c.id?{...x,label:v}:x)}))),area('Beschreibung',c.description,v=>setDoc(d=>({...d,connections:d.connections.map(x=>x.id===c.id?{...x,description:v}:x)}))),h('div',{className:'ref-box'},`${c.source.moduleId}.${c.source.interfaceId||'*'} → ${c.target.moduleId}.${c.target.interfaceId||'*'}`));
  }

  if (selection.type === 'process') {
    const p=doc.processes.find(x=>x.id===selection.id); if(!p)return null;
    return h('div',{className:'panel-body'},h('h2',null,'Process'),field('Name',p.name,v=>patchProcess(p.id,{name:v})),area('Zweck / fachliche Anforderung',p.purpose,v=>patchProcess(p.id,{purpose:v})),area('Vorbedingungen',(p.preconditions||[]).join('\n'),v=>patchProcess(p.id,{preconditions:v.split('\n').filter(Boolean)})),area('Erwartetes Ergebnis',p.expectedResult,v=>patchProcess(p.id,{expectedResult:v})),h('button',{className:'btn primary',onClick:()=>patchProcess(p.id,{steps:[...(p.steps||[]),{id:uid('step'),name:'Neuer Schritt',actor:'MODULE',moduleId:null,interfaceId:null,targetModuleId:null,targetInterfaceId:null,description:''}]})},'+ Prozessschritt'));
  }

  if (selection.type === 'step') {
    const p=doc.processes.find(x=>x.id===selection.processId); const s=p?.steps.find(x=>x.id===selection.id); if(!p||!s)return null;
    const patchMany=changes=>patchProcess(p.id,{steps:p.steps.map(x=>x.id===s.id?{...x,...changes}:x)});
    const currentModule=moduleOptions.find(m=>m.id===s.moduleId);
    const targetModule=moduleOptions.find(m=>m.id===s.targetModuleId);
    return h('div',{className:'panel-body'},h('h2',null,'Process Step'),field('Name',s.name,v=>patchMany({name:v})),selectField('Actor',s.actor||'MODULE',['HUMAN','MODULE'],v=>patchMany({actor:v})),
      optionField('Module',s.moduleId,moduleOptions.map(m=>[m.id,m.name]),v=>patchMany({moduleId:v||null,interfaceId:null})),
      optionField('Interface',s.interfaceId,(currentModule?.interfaces||[]).map(i=>[i.id,i.name]),v=>patchMany({interfaceId:v||null})),
      optionField('Zielmodul',s.targetModuleId,moduleOptions.map(m=>[m.id,m.name]),v=>patchMany({targetModuleId:v||null,targetInterfaceId:null})),
      optionField('Zielinterface',s.targetInterfaceId,(targetModule?.interfaces||[]).map(i=>[i.id,i.name]),v=>patchMany({targetInterfaceId:v||null})),
      area('Beschreibung',s.description,v=>patchMany({description:v})),h('button',{className:'btn danger',onClick:()=>patchProcess(p.id,{steps:p.steps.filter(x=>x.id!==s.id)})},'Schritt löschen'));
  }

  if (selection.type === 'roadmap') {
    const r=doc.roadmap.find(x=>x.id===selection.id); if(!r)return null;
    const patch=(key,val)=>setDoc(d=>({...d,roadmap:d.roadmap.map(x=>x.id===r.id?{...x,[key]:val}:x)}));
    const targets=r.targetType==='process'?(doc.processes||[]).map(p=>[p.id,p.name]):(doc.modules||[]).map(m=>[m.id,m.name]);
    return h('div',{className:'panel-body'},h('h2',null,'Roadmap Item'),field('Name',r.name,v=>patch('name',v)),field('Start',r.start,v=>patch('start',v),'date'),field('Ende',r.end,v=>patch('end',v),'date'),selectField('Status',r.status,['planned','active','blocked','done'],v=>patch('status',v)),selectField('Zieltyp',r.targetType,['module','process'],v=>{patch('targetType',v);patch('targetId','')}),optionField('Ziel',r.targetId,targets,v=>patch('targetId',v)),field('Gruppe',r.group||'',v=>patch('group',v)));
  }
  return null;
}

function field(label,value,onChange,type='text'){return h('div',{className:'field'},h('label',null,label),h('input',{type,value:value||'',onChange:e=>onChange(e.target.value)}));}
function area(label,value,onChange){return h('div',{className:'field'},h('label',null,label),h('textarea',{value:value||'',onChange:e=>onChange(e.target.value)}));}
function selectField(label,value,options,onChange){return h('div',{className:'field'},h('label',null,label),h('select',{value:value||'',onChange:e=>onChange(e.target.value)},...options.map(v=>h('option',{key:v,value:v},v))));}
function optionField(label,value,options,onChange){return h('div',{className:'field'},h('label',null,label),h('select',{value:value||'',onChange:e=>onChange(e.target.value)},h('option',{value:''},'—'),...options.map(([v,n])=>h('option',{key:v,value:v},n))));}

function SidePanel({mode, setMode, doc, setDoc, selection, issues}) {
  return h('aside',{className:'sidepanel'},
    h('div',{className:'panel-tabs'},...['inspector','json','consistency'].map(x=>h('button',{key:x,className:mode===x?'active':'',onClick:()=>setMode(x)},x==='inspector'?'Inspector':x==='json'?'{} JSON':`Checks ${issues.length}`))),
    mode==='inspector' ? h(Inspector,{doc,setDoc,selection}) : null,
    mode==='json' ? h('div',{className:'json-panel'},h('button',{className:'btn',onClick:()=>navigator.clipboard?.writeText(JSON.stringify(doc,null,2))},'Copy JSON'),h('pre',null,JSON.stringify(doc,null,2))) : null,
    mode==='consistency' ? h('div',{className:'panel-body'},issues.length?h(React.Fragment,null,...issues.map((i,n)=>h('div',{className:`issue ${i.level}`,key:n},h('strong',null,i.level.toUpperCase()),h('span',null,i.text)))):h('div',{className:'ok-box'},'Keine Inkonsistenzen gefunden.')) : null
  );
}

function Designer() {
  const [doc,setDocState]=useState(null);
  const [status,setStatus]=useState('loading');
  const [activeView,setActiveView]=useState('architecture');
  const [selection,setSelection]=useState(null);
  const [panelMode,setPanelMode]=useState('inspector');
  const [selectedProcessId,setSelectedProcessId]=useState(null);
  const docRef=useRef(null), timer=useRef(null), saving=useRef(false), queued=useRef(false), loaded=useRef(false);
  useEffect(()=>{docRef.current=doc;},[doc]);

  const load=useCallback(async()=>{setStatus('loading');const r=await fetch(API,{cache:'no-store'});if(!r.ok)throw new Error(`HTTP ${r.status}`);const d=await r.json();docRef.current=d;setDocState(d);loaded.current=true;setStatus('saved');},[]);
  useEffect(()=>{load().catch(e=>{console.error(e);setStatus('error')});},[load]);

  const saveNow=useCallback(async()=>{
    if(!loaded.current||!docRef.current)return;
    if(saving.current){queued.current=true;return;}
    saving.current=true;setStatus('saving');const snap=docRef.current;
    try{const r=await fetch(API,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(snap)});const body=await r.json();if(r.status===409){setStatus('conflict');return;}if(!r.ok)throw new Error(body.message||body.error||`HTTP ${r.status}`);docRef.current=body;setDocState(body);setStatus('saved');}
    catch(e){console.error(e);setStatus('error');}
    finally{saving.current=false;if(queued.current){queued.current=false;saveNow();}}
  },[]);
  const schedule=useCallback(()=>{if(!loaded.current)return;clearTimeout(timer.current);timer.current=setTimeout(saveNow,650);},[saveNow]);
  const setDoc=useCallback(updater=>{setDocState(prev=>{const next=typeof updater==='function'?updater(prev):updater;docRef.current=next;return next;});schedule();},[schedule]);

  if(!doc)return h('div',{className:'loading-screen'},'Designer lädt…');
  const issues=consistencyIssues(doc);
  const errors=issues.filter(i=>i.level==='error').length, warns=issues.filter(i=>i.level==='warn').length;
  const statusLabel=status==='saved'?`gespeichert · v${doc.version}`:status==='saving'?'speichert…':status==='conflict'?'Konflikt · Refresh nötig':status==='loading'?'lädt…':'Fehler';
  const addModule=()=>{const id=uid('module');setDoc(d=>({...d,modules:[...d.modules,{id,name:'NEUES MODUL',description:'',status:'planned',components:[],interfaces:[]}]}));setSelection({type:'module',id});setActiveView('architecture');};
  const addRoadmap=()=>{const id=uid('road');const today=new Date().toISOString().slice(0,10);setDoc(d=>({...d,roadmap:[...d.roadmap,{id,name:'Neuer Roadmap-Eintrag',start:today,end:today,status:'planned',targetType:'module',targetId:'',group:'Gateway'}]}));setSelection({type:'roadmap',id});setActiveView('roadmap');};

  return h('div',{className:'shell'},
    h('header',{className:'topbar'},
      h('div',{className:'brand'},h('div',{className:'brandmark'},'NZ'),h('div',null,h('h1',null,'NEROZON GATEWAY Designer'),h('small',null,'Processes · Architecture · Roadmap'))),
      h('nav',{className:'view-tabs'},...['processes','architecture','roadmap'].map(v=>h('button',{key:v,className:activeView===v?'active':'',onClick:()=>{setActiveView(v);setSelection(null)}},v[0].toUpperCase()+v.slice(1)))),
      h('div',{className:'toolbar'},h('button',{className:`check-badge ${errors?'bad':warns?'warn':'good'}`,onClick:()=>setPanelMode('consistency')},errors?`${errors} ✕`:warns?`${warns} ⚠`:'✓ Consistent'),h('span',{className:`status ${status}`},statusLabel),h('button',{className:'btn',onClick:()=>load().catch(()=>setStatus('error'))},'Refresh'),activeView==='architecture'?h('button',{className:'btn primary',onClick:addModule},'+ Module'):null,activeView==='roadmap'?h('button',{className:'btn primary',onClick:addRoadmap},'+ Item'):null)
    ),
    h('main',{className:'workspace'},
      h('section',{className:'mainview'},
        activeView==='architecture'?h(ArchitectureView,{doc,setDoc,select:setSelection}):null,
        activeView==='processes'?h(ProcessesView,{doc,setDoc,select:setSelection,selectedProcessId,setSelectedProcessId}):null,
        activeView==='roadmap'?h(RoadmapView,{doc,select:setSelection}):null
      ),
      h(SidePanel,{mode:panelMode,setMode:setPanelMode,doc,setDoc,selection,issues})
    )
  );
}

createRoot(document.getElementById('app')).render(h(ReactFlowProvider,null,h(Designer)));
