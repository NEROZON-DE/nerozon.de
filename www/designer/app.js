import React, {useCallback, useEffect, useMemo, useRef, useState} from 'https://esm.sh/react@18.3.1';
import {createRoot} from 'https://esm.sh/react-dom@18.3.1/client';
import {
  ReactFlow,
  ReactFlowProvider,
  Background,
  Controls,
  MiniMap,
  Handle,
  Position,
  addEdge,
  useEdgesState,
  useNodesState,
  useViewport
} from 'https://esm.sh/@xyflow/react@12.11.6?deps=react@18.3.1,react-dom@18.3.1';

const h = React.createElement;
const API = './api/document.php';

function ModuleNode({data}) {
  const {zoom} = useViewport();
  const detailed = zoom >= 0.82;
  const expanded = zoom >= 1.18;
  const interfaces = Array.isArray(data.interfaces) ? data.interfaces : [];

  return h('div', {className: 'node-card'},
    h('div', {className: 'node-head'},
      h('div', {className: 'node-title'}, data.label || 'Module'),
      h('div', {className: 'node-meta'}, `${data.phase || 'Unassigned'} · ${data.status || 'planned'}`)
    ),
    detailed && h('div', {className: 'node-body'},
      expanded && data.description ? h('div', {className: 'node-desc'}, data.description) : null,
      h('div', {className: 'ports'}, interfaces.map((port) => {
        const isOut = port.direction === 'out';
        return h('div', {className: 'port-row', key: port.id},
          !isOut && h(Handle, {type: 'target', position: Position.Left, id: port.id}),
          h('span', {className: 'port-name'}, port.name || port.id),
          expanded ? h('span', {className: 'port-proto'}, port.protocol || '') : null,
          isOut && h(Handle, {type: 'source', position: Position.Right, id: port.id})
        );
      }))
    )
  );
}

const nodeTypes = {module: ModuleNode};

function Inspector({node, phases, onPatch, onDelete}) {
  if (!node) return h('aside', {className: 'inspector'},
    h('h2', null, 'Inspector'),
    h('div', {className: 'empty'}, 'Eine Box auswählen, um Details und Interfaces zu bearbeiten.')
  );

  const data = node.data || {};
  const ports = Array.isArray(data.interfaces) ? data.interfaces : [];
  const patchData = (key, value) => onPatch(node.id, {data: {...data, [key]: value}});
  const patchPort = (index, key, value) => {
    const next = ports.map((port, i) => i === index ? {...port, [key]: value} : port);
    patchData('interfaces', next);
  };
  const addPort = () => {
    const id = `port-${Date.now().toString(36)}`;
    patchData('interfaces', [...ports, {id, name: 'Interface', direction: 'in', protocol: 'HTTP'}]);
  };
  const removePort = (index) => patchData('interfaces', ports.filter((_, i) => i !== index));

  return h('aside', {className: 'inspector'},
    h('h2', null, 'Inspector'),
    h('div', {className: 'field'}, h('label', null, 'Name'), h('input', {value: data.label || '', onChange: e => patchData('label', e.target.value)})),
    h('div', {className: 'field'}, h('label', null, 'Beschreibung'), h('textarea', {value: data.description || '', onChange: e => patchData('description', e.target.value)})),
    h('div', {className: 'field'}, h('label', null, 'Roadmap-Phase'), h('select', {value: data.phase || '', onChange: e => patchData('phase', e.target.value)},
      phases.map(p => h('option', {key: p.id, value: p.name}, p.name))
    )),
    h('div', {className: 'field'}, h('label', null, 'Status'), h('select', {value: data.status || 'planned', onChange: e => patchData('status', e.target.value)},
      ['planned','active','blocked','done'].map(v => h('option', {key: v, value: v}, v))
    )),
    h('h2', null, 'Interfaces'),
    ...ports.map((port, index) => h('div', {className: 'port-editor', key: port.id},
      h('div', {className: 'field'}, h('label', null, 'Name'), h('input', {value: port.name || '', onChange: e => patchPort(index, 'name', e.target.value)})),
      h('div', {className: 'port-editor-grid'},
        h('div', {className: 'field'}, h('label', null, 'Protokoll'), h('input', {value: port.protocol || '', onChange: e => patchPort(index, 'protocol', e.target.value)})),
        h('div', {className: 'field'}, h('label', null, 'Richtung'), h('select', {value: port.direction || 'in', onChange: e => patchPort(index, 'direction', e.target.value)},
          h('option', {value: 'in'}, 'in'), h('option', {value: 'out'}, 'out')
        ))
      ),
      h('button', {className: 'btn danger', onClick: () => removePort(index)}, 'Interface löschen')
    )),
    h('div', {className: 'row'},
      h('button', {className: 'btn', onClick: addPort}, '+ Interface'),
      h('button', {className: 'btn danger', onClick: () => onDelete(node.id)}, 'Box löschen')
    )
  );
}

function Designer() {
  const [nodes, setNodes, onNodesChangeBase] = useNodesState([]);
  const [edges, setEdges, onEdgesChangeBase] = useEdgesState([]);
  const [roadmap, setRoadmap] = useState([]);
  const [version, setVersion] = useState(0);
  const [updatedAt, setUpdatedAt] = useState(null);
  const [selectedId, setSelectedId] = useState(null);
  const [status, setStatus] = useState('loading');
  const [loaded, setLoaded] = useState(false);
  const saveTimer = useRef(null);
  const saving = useRef(false);
  const queued = useRef(false);
  const stateRef = useRef({nodes: [], edges: [], roadmap: [], version: 0});

  useEffect(() => { stateRef.current = {nodes, edges, roadmap, version}; }, [nodes, edges, roadmap, version]);

  const load = useCallback(async () => {
    setStatus('loading');
    const response = await fetch(API, {cache: 'no-store'});
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const doc = await response.json();
    setNodes(doc.nodes || []);
    setEdges(doc.edges || []);
    setRoadmap(doc.roadmap || []);
    setVersion(doc.version || 0);
    setUpdatedAt(doc.updatedAt || null);
    setSelectedId(null);
    setLoaded(true);
    setStatus('saved');
  }, [setNodes, setEdges]);

  useEffect(() => { load().catch(() => setStatus('error')); }, [load]);

  const saveNow = useCallback(async () => {
    if (!loaded) return;
    if (saving.current) { queued.current = true; return; }
    saving.current = true;
    setStatus('saving');
    const snapshot = stateRef.current;
    try {
      const response = await fetch(API, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          version: snapshot.version,
          updatedAt,
          nodes: snapshot.nodes,
          edges: snapshot.edges,
          roadmap: snapshot.roadmap
        })
      });
      const body = await response.json();
      if (response.status === 409) {
        setStatus('conflict');
        return;
      }
      if (!response.ok) throw new Error(body.error || `HTTP ${response.status}`);
      setVersion(body.version);
      setUpdatedAt(body.updatedAt || null);
      stateRef.current = {...stateRef.current, version: body.version};
      setStatus('saved');
    } catch (error) {
      console.error(error);
      setStatus('error');
    } finally {
      saving.current = false;
      if (queued.current) {
        queued.current = false;
        saveNow();
      }
    }
  }, [loaded, updatedAt]);

  const scheduleSave = useCallback(() => {
    if (!loaded) return;
    clearTimeout(saveTimer.current);
    saveTimer.current = setTimeout(saveNow, 650);
  }, [loaded, saveNow]);

  const onNodesChange = useCallback((changes) => {
    onNodesChangeBase(changes);
    if (changes.some(c => c.type !== 'select')) scheduleSave();
  }, [onNodesChangeBase, scheduleSave]);

  const onEdgesChange = useCallback((changes) => {
    onEdgesChangeBase(changes);
    if (changes.some(c => c.type !== 'select')) scheduleSave();
  }, [onEdgesChangeBase, scheduleSave]);

  const onConnect = useCallback((connection) => {
    setEdges(eds => addEdge({...connection, id: `edge-${Date.now().toString(36)}`, label: ''}, eds));
    scheduleSave();
  }, [setEdges, scheduleSave]);

  const patchNode = useCallback((id, patch) => {
    setNodes(list => list.map(n => n.id === id ? {...n, ...patch} : n));
    scheduleSave();
  }, [setNodes, scheduleSave]);

  const deleteNode = useCallback((id) => {
    setNodes(list => list.filter(n => n.id !== id));
    setEdges(list => list.filter(e => e.source !== id && e.target !== id));
    setSelectedId(null);
    scheduleSave();
  }, [setNodes, setEdges, scheduleSave]);

  const addNode = useCallback(() => {
    const id = `module-${Date.now().toString(36)}`;
    const phase = roadmap[0]?.name || 'PoC';
    setNodes(list => [...list, {
      id,
      type: 'module',
      position: {x: 180 + list.length * 35, y: 120 + list.length * 28},
      data: {label: 'Neues Modul', description: '', status: 'planned', phase, interfaces: []}
    }]);
    setSelectedId(id);
    scheduleSave();
  }, [roadmap, setNodes, scheduleSave]);

  const selected = useMemo(() => nodes.find(n => n.id === selectedId) || null, [nodes, selectedId]);
  const statusLabel = status === 'saved' ? `gespeichert · v${version}` : status === 'saving' ? 'speichert…' : status === 'conflict' ? 'Konflikt · Refresh nötig' : status === 'loading' ? 'lädt…' : 'Fehler';

  return h('div', {className: 'shell'},
    h('header', {className: 'topbar'},
      h('div', {className: 'brand'}, h('div', {className: 'brandmark'}, 'NZ'), h('div', null, h('h1', null, 'NEROZON Designer'), h('small', null, 'Architecture + Roadmap PoC'))),
      h('div', {className: 'toolbar'},
        h('span', {className: `status ${status}`}, statusLabel),
        h('button', {className: 'btn', onClick: () => load().catch(() => setStatus('error'))}, 'Refresh'),
        h('button', {className: 'btn primary', onClick: addNode}, '+ Box')
      )
    ),
    h('main', {className: `layout ${selected ? '' : 'no-selection'}`},
      h('aside', {className: 'roadmap'}, h('h2', null, 'Roadmap'),
        ...roadmap.slice().sort((a,b) => (a.order || 0) - (b.order || 0)).map(phase => {
          const count = nodes.filter(n => n.data?.phase === phase.name).length;
          return h('div', {className: 'phase', key: phase.id}, h('strong', null, phase.name), h('span', null, `${count} Box${count === 1 ? '' : 'en'}`));
        })
      ),
      h('section', {className: 'canvas'},
        h(ReactFlow, {
          nodes, edges, nodeTypes,
          onNodesChange, onEdgesChange, onConnect,
          onNodeClick: (_, node) => setSelectedId(node.id),
          onPaneClick: () => setSelectedId(null),
          fitView: true,
          minZoom: .2,
          maxZoom: 2.2,
          defaultEdgeOptions: {type: 'smoothstep'},
          deleteKeyCode: null
        },
          h(Background, {gap: 20, size: 1}),
          h(MiniMap, {pannable: true, zoomable: true}),
          h(Controls, null)
        ),
        h('div', {className: 'zoom-note'}, 'Zoom: Übersicht → Interfaces → Details')
      ),
      h(Inspector, {node: selected, phases: roadmap, onPatch: patchNode, onDelete: deleteNode})
    )
  );
}

createRoot(document.getElementById('app')).render(h(ReactFlowProvider, null, h(Designer)));
