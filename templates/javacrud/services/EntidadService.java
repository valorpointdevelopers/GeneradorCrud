    package com.{PACKAGE}.services;


    import com.{PACKAGE}.entities.{EntidadM}Entity;
    import com.{PACKAGE}.repositorys.{EntidadRepository};
    import org.springframework.beans.factory.annotation.Autowired;
    import org.springframework.stereotype.Service;

    import java.util.List;
    import java.util.Optional;

    @Service
    public class {EntidadM}Service {

        @Autowired
        private {EntidadM}Repository {entidad}Repository;

        // Crear una nueva entidad
        public {EntidadM}Entity crear{EntidadM}({EntidadM}Entity {entidad}entity) {
            return {entidad}Repository.save({entidad}entity);
        }

        // Obtener todas las entidades
        public List<{EntidadM}Entity> obtener{EntidadM}() {
            return {entidad}Repository.findAll();
        }

        // Obtener una entidad por ID
        public Optional<{EntidadM}Entity> obtener{EntidadM}PorId(Integer id) {
            return {entidad}Repository.findById(id);
        }

        // Actualizar una entidad
        public {EntidadM}Entity actualizar{EntidadM}(Integer id, {EntidadM}Entity {entidad}Actualizada) {
            {EntidadM}Entity {entidad}Existente = {entidad}Repository.findById(id).orElseThrow(() -> new RuntimeException("{EntidadM} no encontrada"));

            {COLUMNS}

            return {entidad}Repository.save({entidad}Existente);
        }

        // Eliminar una entidad
        public void eliminar{EntidadM}(Integer id) {
            {entidad}Repository.deleteById(id);
        }
    }