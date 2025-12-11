package com.chorjuan.controllers;



import com.chorjuan.entities.ChorjuanEntity;

import com.chorjuan.services.ChorjuanService;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/chorjuan")
public class ChorjuanController {

    @Autowired
    private ChorjuanService chorjuanService;

    // Crear una nueva entidad
    @PostMapping("/crearchorjuan")
    public ResponseEntity<ChorjuanEntity>crearChorjuanEntity(@RequestBody ChorjuanEntity chorjuan) {
        ChorjuanEntity nuevachorjuan = chorjuanService.crearChorjuan(chorjuan);
        return ResponseEntity.ok(nuevachorjuan);
    }

    // Obtener todas las entidades
    @GetMapping("/listarchorjuan")
    public ResponseEntity<List<ChorjuanEntity>> obtenerChorjuanEntity() {
        List<ChorjuanEntity> chorjuan = chorjuanService.obtenerChorjuan();

    return ResponseEntity.ok(chorjuan);
}

// Obtener una entidad por ID
    @GetMapping("/obtenerchorjuan/{id}")
    public ResponseEntity<ChorjuanEntity>obtenerChorjuanPorId(@PathVariable Integer id) {
    return chorjuanService.obtenerChorjuanPorId(id)
            .map(ResponseEntity::ok)
            .orElse(ResponseEntity.notFound().build());
}

// Actualizar una entidad
    @PutMapping("/actualizarchorjuan/{id}")
    public ResponseEntity<ChorjuanEntity> actualizarChorjuan(@PathVariable Integer id, @RequestBody ChorjuanEntity chorjuanentity) {
        ChorjuanEntity chorjuanActualizada =chorjuanService.actualizarChorjuan(id, chorjuanentity);
        return ResponseEntity.ok(chorjuanActualizada);
        }

// Eliminar una entidad
@DeleteMapping("/eliminarchorjuan/{id}")
public ResponseEntity<Void> eliminarChorjuanEntity(@PathVariable Integer id) {
        chorjuanService.eliminarChorjuan(id);
        return ResponseEntity.noContent().build();
    }
            }