package com.chorjuan.entities;

import java.time.LocalDate;



import jakarta.persistence.*;
//[[nombre, tipo],[edad, int]]
@Entity
@Table(name = "chorjuan")
public class ChorjuanEntity {


        @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(name = "idChorjuan", nullable = false)
    private Integer idChorjuan;

    @Column(name = "nombre", nullable = false)
    private String nombre;

    @Column(name = "fecha", nullable = false)
    private LocalDate fecha;

    @Column(name = "hora", nullable = false)
    private String hora;



    // Constructor vacío
    public ChorjuanEntity() {
    }

    // Constructor con parámetros
    public ChorjuanEntity(Integer idChorjuan, String nombre, LocalDate fecha, String hora) {
                this.idChorjuan = idChorjuan;
        this.nombre = nombre;
        this.fecha = fecha;
        this.hora = hora;
    }

    // Getters y Setters

        public Integer getIdChorjuan() {
        return idChorjuan;
    }

    public void setIdChorjuan(Integer idChorjuan) {
        this.idChorjuan = idChorjuan;
    }

    public String getNombre() {
        return nombre;
    }

    public void setNombre(String nombre) {
        this.nombre = nombre;
    }

    public LocalDate getFecha() {
        return fecha;
    }

    public void setFecha(LocalDate fecha) {
        this.fecha = fecha;
    }

    public String getHora() {
        return hora;
    }

    public void setHora(String hora) {
        this.hora = hora;
    }

}