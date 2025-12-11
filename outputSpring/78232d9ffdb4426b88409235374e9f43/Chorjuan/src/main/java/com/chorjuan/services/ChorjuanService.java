package com.chorjuan.services;




    import com.chorjuan.entities.ChorjuanEntity;

    import com.chorjuan.repository.ChorjuanRepository;

    import org.springframework.beans.factory.annotation.Autowired;
    import org.springframework.stereotype.Service;

    import java.util.List;
    import java.util.Optional;

    @Service
    public class ChorjuanService {

        @Autowired
        private ChorjuanRepository chorjuanRepository;

        // Crear una nueva entidad
        public ChorjuanEntity crearChorjuan(ChorjuanEntity chorjuanentity) {
            return chorjuanRepository.save(chorjuanentity);
        }

        // Obtener todas las entidades
        public List<ChorjuanEntity> obtenerChorjuan() {
            return chorjuanRepository.findAll();
        }

        // Obtener una entidad por ID
        public Optional<ChorjuanEntity> obtenerChorjuanPorId(Integer id) {
            return chorjuanRepository.findById(id);
        }

        // Actualizar una entidad
        public ChorjuanEntity actualizarChorjuan(Integer id, ChorjuanEntity chorjuanActualizada) {
            ChorjuanEntity chorjuanExistente = chorjuanRepository.findById(id).orElseThrow(() -> new RuntimeException("Chorjuan no encontrada"));

                    chorjuanExistente.setNombre(chorjuanActualizada.getNombre());
        chorjuanExistente.setFecha(chorjuanActualizada.getFecha());
        chorjuanExistente.setHora(chorjuanActualizada.getHora());


            return chorjuanRepository.save(chorjuanExistente);
        }

        // Eliminar una entidad
        public void eliminarChorjuan(Integer id) {
            chorjuanRepository.deleteById(id);
        }
    }