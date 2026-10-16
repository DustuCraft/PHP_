package com.example.demo1;

import javafx.application.Platform;
import javafx.beans.binding.Bindings;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.CheckBox;
import javafx.scene.control.ComboBox;
import javafx.scene.control.DatePicker;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.scene.control.Toggle;
import javafx.scene.control.ToggleGroup;

import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Pattern;

public class HelloController {

    private static final Pattern EMAIL_PATTERN =
            Pattern.compile("^[\\w.+-]+@[\\w-]+(\\.[\\w-]+)+$");

    @FXML private TextField fioField;
    @FXML private TextField emailField;
    @FXML private TextField groupField;
    @FXML private ComboBox<String> cityCombo;
    @FXML private DatePicker birthDatePicker;

    @FXML private ToggleGroup courseGroup;
    @FXML private ToggleGroup formGroup;

    @FXML private CheckBox sportCheck;
    @FXML private CheckBox scienceCheck;
    @FXML private CheckBox creativityCheck;
    @FXML private CheckBox dormCheck;
    @FXML private CheckBox consentCheck;

    @FXML private Button generateButton;
    @FXML private Button clearButton;
    @FXML private Button exitButton;

    @FXML private TextArea resultArea;

    @FXML
    private void initialize() {
        // «Сформировать» недоступна, пока ФИО пустое
        generateButton.disableProperty().bind(
                Bindings.createBooleanBinding(
                        () -> fioField.getText() == null || fioField.getText().trim().isEmpty(),
                        fioField.textProperty()));
    }

    @FXML
    private void onGenerate() {
        String fio = fioField.getText() == null ? "" : fioField.getText().trim();
        String email = emailField.getText() == null ? "" : emailField.getText().trim();
        String group = groupField.getText() == null ? "" : groupField.getText().trim();

        // --- Валидация ---
        if (fio.isEmpty()) {
            showAlert("Поле «ФИО» обязательно для заполнения.");
            fioField.requestFocus();
            return;
        }
        if (!EMAIL_PATTERN.matcher(email).matches()) {
            showAlert("Email имеет некорректный формат (пример: name@example.com).");
            emailField.requestFocus();
            return;
        }
        if (cityCombo.getValue() == null) {
            showAlert("Выберите город.");
            cityCombo.requestFocus();
            return;
        }
        LocalDate birth = birthDatePicker.getValue();
        if (birth == null) {
            showAlert("Укажите дату рождения.");
            birthDatePicker.requestFocus();
            return;
        }
        if (birth.isAfter(LocalDate.now())) {
            showAlert("Дата рождения не может быть в будущем.");
            birthDatePicker.requestFocus();
            return;
        }
        Toggle course = courseGroup.getSelectedToggle();
        if (course == null) {
            showAlert("Необходимо выбрать курс.");
            return;
        }
        Toggle form = formGroup.getSelectedToggle();
        if (form == null) {
            showAlert("Необходимо выбрать форму обучения.");
            return;
        }
        if (!consentCheck.isSelected()) {
            showAlert("Формирование анкеты невозможно без согласия на обработку данных.");
            consentCheck.requestFocus();
            return;
        }

        // --- Сбор отмеченных признаков ---
        List<String> features = new ArrayList<>();
        if (sportCheck.isSelected()) features.add(sportCheck.getText());
        if (scienceCheck.isSelected()) features.add(scienceCheck.getText());
        if (creativityCheck.isSelected()) features.add(creativityCheck.getText());
        if (dormCheck.isSelected()) features.add(dormCheck.getText());

        // --- Формирование карточки ---
        StringBuilder sb = new StringBuilder();
        sb.append("КАРТОЧКА СТУДЕНТА\n");
        sb.append("------------------------------\n");
        sb.append("ФИО: ").append(fio).append("\n");
        sb.append("Email: ").append(email).append("\n");
        sb.append("Группа: ").append(group.isEmpty() ? "не указана" : group).append("\n");
        sb.append("Город: ").append(cityCombo.getValue()).append("\n");
        sb.append("Дата рождения: ")
                .append(birth.format(DateTimeFormatter.ofPattern("dd.MM.yyyy"))).append("\n");
        sb.append("Курс: ").append(course.getUserData()).append("\n");
        sb.append("Форма обучения: ").append(form.getUserData()).append("\n");
        sb.append("Признаки: ")
                .append(features.isEmpty() ? "не выбраны" : String.join(", ", features)).append("\n");
        sb.append("Согласие на обработку данных: да");

        resultArea.setText(sb.toString());
    }

    @FXML
    private void onClear() {
        fioField.clear();
        emailField.clear();
        groupField.clear();
        cityCombo.setValue(null);
        birthDatePicker.setValue(null);
        birthDatePicker.getEditor().clear();
        courseGroup.selectToggle(null);
        formGroup.selectToggle(null);
        sportCheck.setSelected(false);
        scienceCheck.setSelected(false);
        creativityCheck.setSelected(false);
        dormCheck.setSelected(false);
        consentCheck.setSelected(false);
        resultArea.clear();
    }

    @FXML
    private void onExit() {
        Platform.exit();
    }

    private void showAlert(String message) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle("Ошибка ввода");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}