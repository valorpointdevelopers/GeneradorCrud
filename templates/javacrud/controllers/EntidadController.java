package com.{PACKAGE}.controllers;

import com.{PACKAGE}.entities.{EntidadM}Entity;
import com.{PACKAGE}.services.{EntidadService};
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/{entidad}")
public class {EntidadM}Controller {

    @Autowired
    private {EntidadM}Service {entidad}Service;

    // Crear una nueva entidad
    @PostMapping("/crear{entidad}")
    public ResponseEntity<{EntidadM}Entity>crear{EntidadM}Entity(@RequestBody {ENTIDAD}Entity {entidad}) {
        {EntidadM}Entity nueva{entidad} = {entidad}Service.crear{EntidadM}({entidad});
        return ResponseEntity.ok(nueva{entidad});
    }

    // Obtener todas las entidades
    @GetMapping("/listar{entidad}")
    public ResponseEntity<List<{EntidadM}Entity>> obtener{EntidadM}Entity() {
        List<{EntidadM}Entity> {entidad} = {entidad}Service.obtener{EntidadM}();

    return ResponseEntity.ok({entidad});
}

// Obtener una entidad por ID
    @GetMapping("/obtener{entidad}/{id}")
    public ResponseEntity<{EntidadM}Entity>obtener{EntidadM}PorId(@PathVariable Integer id) {
    return {entidad}Service.obtener{EntidadM}PorId(id)
            .map(ResponseEntity::ok)
            .orElse(ResponseEntity.notFound().build());
}

// Actualizar una entidad
    @PutMapping("/actualizar{entidad}/{id}")
    public ResponseEntity<{EntidadM}Entity> actualizar{EntidadM}(@PathVariable Integer id, @RequestBody {ENTIDAD}Entity {entidad}entity) {
        {EntidadM}Entity {entidad}Actualizada ={entidad}Service.actualizar{EntidadM}(id, {entidad}entity);
        return ResponseEntity.ok({entidad}Actualizada);
        }

// Eliminar una entidad
@DeleteMapping("/eliminar{entidad}/{id}")
public ResponseEntity<Void> eliminar{EntidadM}Entity(@PathVariable Integer id) {
        {entidad}Service.eliminar{EntidadM}(id);
        return ResponseEntity.noContent().build();
    }
            }
