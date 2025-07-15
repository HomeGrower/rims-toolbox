# DatastoreBuilder Feature-Dokumentation

## Übersicht
Diese Dokumentation beschreibt alle Features, die für den DatastoreBuilder Toolbox implementiert wurden, bevor er mit der Projekt-Version zusammengeführt wurde.

## Implementierte Features

### 1. Visuelle Änderungsindikatoren
- **Table Modified Indicator**
  - Zeigt an, wenn eine Tabelle vom Basis-Zustand abweicht
  - CSS-Klasse `modified` auf Table-Headers
  - Erscheint bei jeder Feld- oder Property-Änderung
  
- **Field Modified Indicator**
  - Visuelle Anzeige für modifizierte Felder
  - Implementiert über `'modified': isFieldModified(selectedTable, fieldKey)`

### 2. Override-Felder Verwaltung
- **Condition Fields (Bedingungs-Felder)**
  - Spezielle Behandlung für: start, end, seasons, periodes, buildings
  - Getrennte Darstellung von normalen Override-Feldern
  - Hellblauer Hintergrund (`background: #f0f9ff`)
  
- **Override Field Preview**
  - Kompakte, ausklappbare Vorschau
  - Zeigt Zusammenfassung mit Titel und Feldanzahl
  - Expandierbar/Kollabierbar mit rotierendem Pfeil
  - Zeigt Feldname und Typ in kompakter Form

### 3. Auto-Scrolling Feature
- **scrollToTable Funktion**
  - Automatisches Scrollen zur ausgewählten Tabelle
  - Aktiviert wenn `liveEditEnabled` true ist
  - Smooth-Scrolling mit Animation

### 4. Subtable-Felder Handhabung
- **Korrektes Merging**
  - Richtige Behandlung verschachtelter Feldstrukturen
  - Spezielle Diff-Verwaltung für Subtable-Felder
  - Nur Unterschiede zur Basis werden gespeichert

### 5. UI/UX Verbesserungen
- **Button-Abstände**
  - "Add Field" Button mit `margin-top: 1rem`
  - Richtige Abstände bei Override-Sections
  
- **Visual Preview Panel**
  - Sortierte Tabellen-Anzeige
  - Alphabetische Sortierung
  - Computed Property `sortedCurrentTables`

### 6. Such- und Filter-Funktionen
- **Tabellen-Suche**
  - Suche nach Name oder Label
  - Echtzeit-Filterung
  
- **Active Tables Filter**
  - Checkbox für "Show only active tables"
  - Kombinierte Filterung mit Suche

### 7. Custom Table Support
- **Custom Table Indikator**
  - Visuelle Unterscheidung für Custom Tables
  - Edit/Delete Actions nur für Custom Tables
  - Inline-Bearbeitung von Tabellennamen

### 8. Import JSON Funktion (Toolbox-Only)
- **JSON Import im Diff Tab**
  - Sub-Tabs: "Import JSON" und "Current Configuration"
  - Textarea für JSON-Eingabe
  - Error/Success Messages
  - `parseImportedJson` Funktion
  - Nur sichtbar wenn `isToolbox: true`

### 9. Konfiguration Diff-Management
- **Intelligente Diff-Verfolgung**
  - Nur tatsächliche Unterschiede werden gespeichert
  - Automatische Bereinigung leerer Objekte
  - "No customizations made yet" bei keinen Änderungen

### 10. Erweiterte Feld-Typen
- **Standard-Typen**: text, email, url, number, textarea, select, checkbox/boolean
- **Spezial-Typen**: 
  - subtable mit verschachtelten Feldern
  - reference Felder
  - Override-Felder mit Conditions

### 11. Responsive Layout
- **Drei-Panel Layout**
  - Links: Tabellen-Liste
  - Mitte: Editor
  - Rechts: Preview/Diff
- **Unabhängiges Scrolling** pro Panel
- **Overflow Handling** mit `overflow-y: auto`

### 12. Visuelle Gestaltung
- **Farbcodierung**
  - Condition Fields: Hellblau (`#f0f9ff`)
  - Disabled Items: Grau
  - Active/Selected: Hervorgehoben
  - Modified: Spezial-Indikator
  
- **Konsistente Abstände**
  - Einheitliche margins und padding
  - Abgerundete Ecken (border-radius)

### 13. Fehlerprävention
- **Validierung**
  - Verhindert doppelte Tabellen-/Feldnamen
  - Singleton-Tabellen können keine Order-Felder haben
  
- **State Management**
  - Reaktive Vue.js State-Verwaltung
  - Proper cleanup bei Änderungen

### 14. Performance-Optimierungen
- **Computed Properties**
  - Effiziente Filterung und Sortierung
  - Caching von berechneten Werten
  
- **Reaktivität**
  - Nur notwendige Updates
  - Optimierte Re-Renders

## Integration mit Projekt-Modus

Nach der Zusammenführung sollte die Komponente:
1. Alle oben genannten Features beibehalten
2. Mit `isToolbox` prop zwischen Modi unterscheiden
3. Import JSON nur im Toolbox-Modus zeigen
4. Projekt-spezifische Features (Conditions, Chain-Overrides) zusätzlich unterstützen

## Technische Details

### Props
```javascript
isToolbox: Boolean // Unterscheidet zwischen Toolbox und Projekt-Modus
projectId: Number
masterTemplate: Object
defaultStructure: Object
configuration: Object
projectModules: Object
chainCode: String
allowedTables: Array
```

### Wichtige Methoden
- `scrollToTable(tableKey)` - Auto-Scrolling
- `isTableModified(tableKey)` - Prüft Tabellen-Änderungen
- `isFieldModified(tableKey, fieldKey)` - Prüft Feld-Änderungen
- `getConditionFields(fields)` - Filtert Condition-Felder
- `getOverrideFields(fields)` - Filtert Override-Felder
- `parseImportedJson()` - JSON Import (Toolbox only)

Diese Features sollten alle in der finalen zusammengeführten Version vorhanden sein.