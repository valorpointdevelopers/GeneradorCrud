package com.chorjuan.utils;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

@Configuration
public class WebConfig {

    @Bean
    public WebMvcConfigurer corsConfigurer() {
        return new WebMvcConfigurer() {
            @Override
            public void addCorsMappings(CorsRegistry registry) {
                registry.addMapping("/**") // Permitir todos los endpoints
                        .allowedOrigins("http://localhost:4200", "http://localhost:5173") // Permitir el origen específico de tu frontend
                        .allowedMethods("GET", "POST", "PUT", "DELETE", "OPTIONS") // Asegúrate de incluir OPTIONS
                        .allowedHeaders("*"); // Permitir todos los encabezados
            }
        };
    }
}