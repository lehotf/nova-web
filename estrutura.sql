-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 07/04/2026 às 16:48
-- Versão do servidor: 10.11.14-MariaDB-0+deb12u2
-- Versão do PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `eupens16_eupenso`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `links`
--

CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `basepath` varchar(25) NOT NULL,
  `thumb_titulo` text NOT NULL,
  `thumb_titulo_html` text NOT NULL,
  `path` text NOT NULL,
  `titulo` text NOT NULL,
  `subtitulo` text NOT NULL,
  `datePublished` timestamp NULL DEFAULT NULL,
  `dateModified` timestamp NULL DEFAULT NULL,
  `thumb` int(11) NOT NULL,
  `publicado` tinyint(4) NOT NULL,
  `root` tinyint(4) NOT NULL,
  `ultimos` tinyint(4) NOT NULL,
  `search` tinyint(1) NOT NULL,
  `amp` tinyint(1) NOT NULL,
  `duracao` time NOT NULL,
  `keywords` text NOT NULL,
  `artigo` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `links_destaques`
--

CREATE TABLE `links_destaques` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL,
  `linkID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `links_tags`
--

CREATE TABLE `links_tags` (
  `linkID` int(11) NOT NULL,
  `tagID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `script`
--

CREATE TABLE `script` (
  `id` int(11) NOT NULL,
  `m` text DEFAULT NULL,
  `autorizacao` int(11) NOT NULL,
  `ordem` int(11) NOT NULL,
  `eval` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `nome` varchar(25) NOT NULL,
  `path` varchar(25) NOT NULL,
  `destaque` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `login` varchar(80) NOT NULL,
  `senha` varchar(80) NOT NULL,
  `autorizacao` int(11) NOT NULL,
  `nome` varchar(60) NOT NULL,
  `idioma` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `refresh_token` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `links`
--
ALTER TABLE `links`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `links_destaques`
--
ALTER TABLE `links_destaques`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `script`
--
ALTER TABLE `script`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `links`
--
ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `script`
--
ALTER TABLE `script`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
