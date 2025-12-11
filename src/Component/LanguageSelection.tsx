import React, { useState, useEffect } from 'react';
import { generarUUID } from './generadorUUID';
import config from '../config.json';

interface LanguageSelectionProps {
  sessionUUID: string | null;
  show: boolean;
  onClose: () => void;
  tableName: string;
  columns: any[];
  onGenerate: (framework: string) => void;

}

const LanguageSelection: React.FC<LanguageSelectionProps> = ({ sessionUUID, onClose, onGenerate, show, tableName, columns }) => {
  const [language, setLanguage] = useState<string | null>(null);
  const [architecture, setArchitecture] = useState<string | null>(null);
  const [framework, setFramework] = useState<string | null>(null);
  const [showSaveModal, setShowSaveModal] = useState<boolean>(false);

 


const handleLanguageChange = async (selectedLanguage: string) => {
  setLanguage(selectedLanguage);
  setArchitecture(null);
  setFramework(null);

  if (selectedLanguage === 'Java') {
    // Selecciona automáticamente MVC
    setArchitecture('MVC');

    
    onGenerate('Spring');

    
    // Evitar que aparezca el modal "Guardar configuración"
    setShowSaveModal(false);

    
    // esto evita que se cierre el modal y fuerza a mostrar el menú siguiente
    setFramework(null);

    return;
  }
};

  
  

useEffect(() => {
  if (show) {
    fetchTableData(tableName, columns, 'Spring');
  }
}, [show, tableName, columns]);




const fetchTableData = async (tableNameParam?: string, columnsParam?: any[], frameworkParam?: string) => {
  try {
    const payload = {
      tableName: tableNameParam || tableName,
      columns: columnsParam || columns,
      framework: frameworkParam || 'Spring',
      databaseName: sessionUUID
    };

    const response = await fetch('http://localhost/adminlte-dashboard/src/Component/CallerLenguage.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const text = await response.text();
    let data = null;

    try {
      data = JSON.parse(text);
    } catch {
      console.warn(' Respuesta de CallerLenguage no es JSON válido:', text);
      return;
    }

    console.log(' Datos CallerLenguage:', data);

    if (data.tableName && data.columns) {
      console.log(' Tabla procesada correctamente:', data.tableName);
      console.log(' Columnas:', data.columns);
    } else { 
      
      if (data.generatorResult) {
          console.log('Generator result:', data.generatorResult);
        }
        console.warn('Faltan tableName o columns en la respuesta:', data);
      }

      return data; // NUEVO: devolver resultado por si se quiere consumir
    } catch (error) {
      console.error('Error al obtener datos desde CallerLenguage:', error);
    }
};




  const callGenerateAngularCrud = async () => {
    if (!tableName || columns.length === 0) {
      console.warn('No hay tableName o columns para Angular');
      return;
    }

    try {
      const payload = {
        tableName,
        columns,
        framework: 'Angular', // NUEVO
        databaseName: sessionUUID // NUEVO
      };

      const response = await fetch('http://localhost/adminlte-dashboard/src/Component/CallerLenguage.php', { // NUEVO
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const text = await response.text();
      if (!text.trim()) return;

      let result;
      try {
        result = JSON.parse(text);
        console.log('Respuesta Angular (via caller):', result);
      } catch {
        console.warn('Respuesta no JSON válida:', text);
      }

      alert('Proyecto Angular generado correctamente (revisa consola para detalles).');
    } catch (error) {
      console.error('Error Angular:', error);
    }
  };



  const callGenerateSpringCrud = async () => {
    if (!tableName || columns.length === 0) {
      console.warn('No hay tableName o columns para Spring');
      return;
    }

    try {
      const response = await fetch('C:\\xamppchido\\htdocs\\adminlte-dashboard\\src\\Component\\generateSpringCrud.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ tableName, columns }),
      });

      const text = await response.text();
      if (!text.trim()) return;

      let result;
      try {
        result = JSON.parse(text);
        console.log(' Respuesta Spring:', result);
      } catch {
        console.warn(' Respuesta no JSON válida:', text);
      }

      alert(' Proyecto Spring Boot generado correctamente.');
      await callGenerateAngularCrud();
    } catch (error) {
      console.error('Error Spring:', error);
    }
  };


  
 const callGenerateVueCrud = async () => {
    if (!tableName || columns.length === 0) {
      console.warn('No hay tableName o columns para Vue');
      return;
    }

    try {
      const payload = {
        tableName,
        columns,
        framework: 'Vue', // NUEVO
        databaseName: sessionUUID   
      };

      const response = await fetch('http://localhost/adminlte-dashboard/src/Component/CallerLenguage.php', { // NUEVO
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const text = await response.text();
      if (!text.trim()) return;

      let result;
      try {
        result = JSON.parse(text);
        console.log('Respuesta Vue (via caller):', result);
      } catch {
        console.warn('Respuesta no JSON válida:', text);
      }

      alert('Proyecto Vue generado correctamente (revisa consola para detalles).');
    } catch (error) {
      console.error('Error Vue:', error);
    }
  };



  const handleArchitectureChange = (selectedArchitecture: string) => {
    setArchitecture(selectedArchitecture);

    if (selectedArchitecture === 'MVC') {
      setFramework(null); // muestra un submenú
    } else {
      setShowSaveModal(true);
    }
  };



  const handleFrameworkSelection = async (selectedFramework: string) => {
    setFramework(selectedFramework);
setShowSaveModal(true);

setTimeout(async () => {
  if (selectedFramework === 'Angular') {
    await fetchTableData(tableName, columns, 'Angular');
    await callGenerateAngularCrud();
  }

  if (selectedFramework === 'Vue') {
    //await fetchTableData(tableName, columns, 'Vue');
    await callGenerateVueCrud();
  }

  if (selectedFramework === 'Spring') {
    await fetchTableData(tableName, columns, 'Spring');
    await callGenerateSpringCrud();
  }
}, 300); // 300 ms, React ya terminó

  };

  const handleSave = () => {
    if (sessionUUID && language && architecture) {
      localStorage.setItem('projectConfig', JSON.stringify({ sessionUUID, language, architecture, framework }));
      alert(`Configuración guardada: ${language} - ${architecture}${framework ? ' - ' + framework : ''}`);
      onClose();
    } else {
      console.error('No se pudo guardar la configuración.');
    }
  };

  const handleCancel = () => {
    setArchitecture(null);
    setFramework(null);
    setShowSaveModal(false);
  };

  if (!show) return null;

  return (
    <div>
      {!showSaveModal && (
        <div className="modal-overlay">
          <div className="modal-language-architecture">
            <h2>Configura tu proyecto</h2>

            {!language && (
              <div className="language-selection">
                <button onClick={() => handleLanguageChange('Java')}><img src="/assets/java.svg" alt="Java Logo" />Java</button>
                <button onClick={() => handleLanguageChange('PHP')}><img src="/assets/php_logo.svg" alt="PHP Logo" />PHP</button>
                <button onClick={onClose} className="close"><i className="fas fa-times"></i></button>
              </div>
            )}

            {language && !architecture && (
              <div className="architecture-selection">
                <h3>Selecciona la arquitectura para {language}</h3>
                <button onClick={() => handleArchitectureChange('MVC')} className="architecture-btn">MVC</button>
                <button onClick={() => handleArchitectureChange('API Rest')} className="architecture-btn">API + Front</button>
                <button onClick={() => setLanguage(null)} className="btn btn-secondary back-button">
                  <i className="fas fa-arrow-left"></i> Regresar
                </button>
              </div>
            )}

            {architecture === 'MVC' && !framework && (
              <div className="framework-selection">
                <h3>Selecciona el framework para {language} + MVC</h3>
                <button onClick={() => handleFrameworkSelection('Angular')}>Angular</button>
                <button onClick={() => handleFrameworkSelection('Vue')}>Vue</button>
                <button onClick={() => setArchitecture(null)} className="btn btn-secondary back-button">
                  <i className="fas fa-arrow-left"></i> Regresar
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {showSaveModal && (
        <>
          <div className="modal-backdrop fade show custom-backdrop"></div>
          <div className="modal fade show" style={{ display: 'block' }} role="dialog">
            <div className="modal-dialog modal-dialog-centered">
              <div className="modal-content">
                <div className="modal-header">
                  <h5 className="modal-title">Guardar Configuración</h5>
                  <button type="button" className="btn-close" aria-label="Close" onClick={handleCancel}></button>
                </div>
                <div className="modal-body">
                  <p>
                    ¿Estás seguro de que deseas guardar esta configuración?
                    <br />
                    <strong>Lenguaje:</strong> {language}<br />
                    <strong>Arquitectura:</strong> {architecture}<br />
                    {framework && (<><strong>Framework:</strong> {framework}</>)}
                  </p>
                </div>
                <div className="modal-footer">
                  <button type="button" className="btn btn-secondary" onClick={handleCancel}>Cancelar</button>
                  <button type="button" className="btn btn-primary" onClick={handleSave}>Aceptar</button>
                </div>
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  );
};

export default LanguageSelection;
